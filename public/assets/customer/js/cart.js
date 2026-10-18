/* Orenjoy: filter menu, keranjang, dan checkout.
   Vanilla JS, tanpa jQuery. Keranjang disimpan di localStorage (hanya id + jumlah);
   nama dan harga selalu dibaca dari halaman, dan server menghitung ulang semuanya. */
(() => {
    "use strict";

    const cfg = Object.assign(
        { taxPercent: 0, checkoutUrl: "/checkout", placeholder: "" },
        window.ORENJOY || {},
    );
    const STORAGE_KEY = "orenjoy_cart_v1";
    const MAX_QTY = 50;

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const rupiah = (n) =>
        "Rp " + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    const esc = (s) =>
        String(s).replace(
            /[&<>"']/g,
            (c) =>
                ({
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#39;",
                })[c],
        );

    const els = {
        drawer: $("#cartDrawer"),
        backdrop: $("#cartBackdrop"),
        form: $("#checkoutForm"),
        empty: $("#cartEmpty"),
        items: $("#cartItems"),
        error: $("#formError"),
        submit: $("#submitBtn"),
        bar: $("#cartBar"),
        search: $("#menuSearch"),
        status: $("#menuStatus"),
    };

    // Script ini hanya untuk halaman menu.
    if (!els.drawer || !els.form || !els.items) return;

    /* ------------------------------------------------------------------
       Data menu (dibaca dari kartu di halaman)
       ------------------------------------------------------------------ */
    const menu = {};
    $$(".oj-card[data-item-id]").forEach((card) => {
        const id = Number(card.dataset.itemId);
        menu[id] = {
            id,
            name: card.dataset.title,
            price: Number(card.dataset.price),
            img: card.dataset.img,
        };
    });

    /* ------------------------------------------------------------------
       Keranjang
       ------------------------------------------------------------------ */
    function loadCart() {
        try {
            const parsed = JSON.parse(
                localStorage.getItem(STORAGE_KEY) || "[]",
            );
            if (!Array.isArray(parsed)) return [];

            const merged = {};
            parsed.forEach((row) => {
                const id = Number(row && row.id);
                const qty = Math.floor(Number(row && row.qty));
                // Buang menu yang sudah tidak ada di halaman (dihapus / dinonaktifkan).
                if (menu[id] && qty >= 1)
                    merged[id] = Math.min((merged[id] || 0) + qty, MAX_QTY);
            });

            return Object.keys(merged).map((id) => ({
                id: Number(id),
                qty: merged[id],
            }));
        } catch (e) {
            return [];
        }
    }

    function saveCart() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
        } catch (e) {
            /* Mode privat: keranjang tetap jalan, hanya tidak tersimpan. */
        }
    }

    let cart = loadCart();

    const qtyOf = (id) => {
        const row = cart.find((r) => r.id === id);
        return row ? row.qty : 0;
    };

    function setQty(id, qty) {
        qty = Math.min(Math.max(0, qty), MAX_QTY);
        const index = cart.findIndex((r) => r.id === id);

        if (qty === 0) {
            if (index > -1) cart.splice(index, 1);
        } else if (index > -1) {
            cart[index].qty = qty;
        } else {
            cart.push({ id, qty });
        }

        saveCart();
        render();
    }

    // Rumus sama dengan server: pajak dihitung per baris lalu dibulatkan.
    function totals() {
        let sub = 0;
        let tax = 0;
        let count = 0;

        cart.forEach(({ id, qty }) => {
            const line = menu[id].price * qty;
            sub += line;
            tax += Math.round((line * cfg.taxPercent) / 100);
            count += qty;
        });

        return { sub, tax, total: sub + tax, count };
    }

    /* ------------------------------------------------------------------
       Tampilan
       ------------------------------------------------------------------ */
    function render() {
        const t = totals();

        // Kartu menu: tombol "Tambah" berubah jadi stepper jika sudah dipesan.
        $$(".oj-card[data-item-id]").forEach((card) => {
            const q = qtyOf(Number(card.dataset.itemId));
            $('[data-act="add"]', card).hidden = q > 0;
            $(".js-stepper", card).hidden = q === 0;
            $(".js-qty", card).textContent = q;
            $('.js-stepper [data-act="inc"]', card).disabled = q >= MAX_QTY;
        });

        // Badge + bar melayang
        $$(".js-cart-count").forEach((badge) => {
            badge.textContent = t.count;
            badge.hidden = t.count === 0;
        });
        $$(".oj-cart-btn").forEach((btn) => {
            btn.setAttribute(
                "aria-label",
                t.count ? `Buka pesanan, ${t.count} item` : "Buka pesanan",
            );
        });
        if (els.bar) {
            els.bar.hidden = t.count === 0;
            $(".js-bar-count", els.bar).textContent = `${t.count} item`;
            $(".js-bar-total", els.bar).textContent = rupiah(t.total);
        }

        // Isi drawer
        els.empty.hidden = cart.length > 0;
        els.form.hidden = cart.length === 0;

        els.items.innerHTML = cart
            .map(({ id, qty }) => {
                const m = menu[id];
                const name = esc(m.name);
                return `
                <li class="oj-line">
                    <img class="oj-line-img" src="${esc(m.img)}" alt="" loading="lazy"
                         onerror="this.onerror=null;this.src='${esc(cfg.placeholder)}'">
                    <div class="oj-line-info">
                        <div class="oj-line-name">${name}</div>
                        <div class="oj-line-price">${rupiah(m.price)} &times; ${qty} = <strong>${rupiah(m.price * qty)}</strong></div>
                    </div>
                    <div class="oj-stepper" role="group" aria-label="Jumlah ${name}">
                        <button type="button" data-act="dec" data-id="${id}"
                                aria-label="${qty === 1 ? "Hapus" : "Kurangi"} ${name}">${qty === 1 ? '<i class="fas fa-trash-alt" aria-hidden="true"></i>' : "&minus;"}</button>
                        <span aria-live="polite">${qty}</span>
                        <button type="button" data-act="inc" data-id="${id}"
                                aria-label="Tambah ${name}"${qty >= MAX_QTY ? " disabled" : ""}>+</button>
                    </div>
                </li>`;
            })
            .join("");

        $(".js-subtotal", els.drawer).textContent = rupiah(t.sub);
        $(".js-tax-label", els.drawer).textContent = cfg.taxPercent
            ? `Pajak (${cfg.taxPercent}%)`
            : "Pajak";
        $(".js-tax", els.drawer).textContent = rupiah(t.tax);
        $(".js-total", els.drawer).textContent = rupiah(t.total);
    }

    // Setelah render ulang, kembalikan fokus keyboard ke tempat yang masuk akal.
    function restoreFocus(btn, id) {
        const inDrawer = !!btn.closest("#cartDrawer");
        let target = null;

        if (inDrawer) {
            target =
                qtyOf(id) > 0
                    ? $(
                          `#cartItems [data-act="${btn.dataset.act}"][data-id="${id}"]`,
                      )
                    : els.drawer;
        } else {
            if (btn.isConnected && btn.offsetParent !== null) return; // tombolnya masih terlihat
            const card = $(`.oj-card[data-item-id="${id}"]`);
            target =
                qtyOf(id) > 0
                    ? $('.js-stepper [data-act="inc"]', card)
                    : $('[data-act="add"]', card);
        }

        if (target) target.focus();
    }

    document.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-act]");
        if (!btn) return;

        const id = Number(btn.dataset.id);
        if (!menu[id]) return;

        const current = qtyOf(id);
        if (btn.dataset.act === "add" || btn.dataset.act === "inc")
            setQty(id, current + 1);
        else if (btn.dataset.act === "dec") setQty(id, current - 1);
        else return;

        restoreFocus(btn, id);
    });

    /* ------------------------------------------------------------------
       Drawer: buka / tutup, Esc, jebakan fokus
       ------------------------------------------------------------------ */
    let lastFocus = null;
    const isOpen = () => els.drawer.classList.contains("open");

    function openCart() {
        lastFocus = document.activeElement;
        els.drawer.classList.add("open");
        els.backdrop.classList.add("show");
        document.body.classList.add("oj-lock");
        requestAnimationFrame(() => els.drawer.focus());
    }

    function closeCart() {
        els.drawer.classList.remove("open");
        els.backdrop.classList.remove("show");
        document.body.classList.remove("oj-lock");
        if (lastFocus && typeof lastFocus.focus === "function")
            lastFocus.focus();
    }

    $$(".js-open-cart").forEach((btn) =>
        btn.addEventListener("click", openCart),
    );
    $$(".js-close-cart").forEach((btn) =>
        btn.addEventListener("click", closeCart),
    );
    els.backdrop.addEventListener("click", closeCart);

    document.addEventListener("keydown", (e) => {
        if (!isOpen()) return;

        if (e.key === "Escape") {
            closeCart();
            return;
        }

        if (e.key !== "Tab") return;

        const focusable = $$(
            'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])',
            els.drawer,
        ).filter((el) => el.offsetParent !== null);
        if (!focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (
            e.shiftKey &&
            (document.activeElement === first ||
                document.activeElement === els.drawer)
        ) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

    /* ------------------------------------------------------------------
       Cari + filter kategori
       ------------------------------------------------------------------ */
    const chips = $$(".oj-chip");
    const sections = $$(".oj-section");
    let activeFilter = "all";

    function applyFilter() {
        const q = els.search ? els.search.value.trim().toLowerCase() : "";
        let visible = 0;

        sections.forEach((section) => {
            const inCategory =
                activeFilter === "all" ||
                section.dataset.section === activeFilter;
            let shown = 0;

            $$(".oj-card", section).forEach((card) => {
                const match =
                    inCategory && (!q || card.dataset.search.includes(q));
                card.closest(".oj-col").hidden = !match;
                if (match) shown += 1;
            });

            section.hidden = shown === 0;
            visible += shown;
        });

        if (els.status) {
            els.status.textContent =
                visible === 0 && sections.length
                    ? "Menu tidak ditemukan. Coba kata kunci atau kategori lain."
                    : "";
        }
    }

    chips.forEach((chip) =>
        chip.addEventListener("click", () => {
            activeFilter = chip.dataset.filter;
            chips.forEach((c) =>
                c.setAttribute("aria-pressed", String(c === chip)),
            );
            applyFilter();
        }),
    );

    if (els.search) els.search.addEventListener("input", applyFilter);

    /* ------------------------------------------------------------------
       Checkout
       ------------------------------------------------------------------ */
    let busy = false;

    function setBusy(state) {
        busy = state;
        els.submit.disabled = state;
        $(".js-submit-label", els.submit).textContent = state
            ? "Mengirim…"
            : "Kirim Pesanan";
    }

    function showErrors(messages) {
        els.error.innerHTML =
            "<strong>Pesanan belum bisa dikirim:</strong>" +
            '<ul class="mb-0 ps-3">' +
            messages.map((m) => `<li>${esc(m)}</li>`).join("") +
            "</ul>";
        els.error.hidden = false;
        els.error.focus();
        els.error.scrollIntoView({ block: "nearest" });
    }

    const csrfToken = () => {
        const meta = $('meta[name="csrf-token"]');
        return meta ? meta.content : "";
    };

    els.form.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (busy || !cart.length) return;

        els.error.hidden = true;

        const f = els.form.elements;
        const payload = {
            customer_name: f.customer_name.value.trim(),
            payment_method: f.payment_method.value,
            note: f.note.value.trim(),
            items: cart.map(({ id, qty }) => ({ id, qty })),
        };

        setBusy(true);

        try {
            const res = await fetch(cfg.checkoutUrl, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json().catch(() => ({}));

            if (res.status === 422) {
                const list = Object.values(data.errors || {}).flat();
                showErrors(
                    list.length ? list : ["Data pesanan belum lengkap."],
                );
            } else if (res.status === 419) {
                showErrors([
                    "Sesi halaman sudah berakhir. Muat ulang halaman lalu kirim lagi.",
                ]);
            } else if (res.status === 429) {
                showErrors([
                    "Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.",
                ]);
            } else if (!res.ok || !data.redirect) {
                showErrors([
                    data.message ||
                        "Terjadi kesalahan di server. Coba lagi sebentar lagi.",
                ]);
            } else {
                cart = [];
                saveCart();
                window.location.href = data.redirect;
                return; // biarkan tombol tetap nonaktif selama berpindah halaman
            }
        } catch (err) {
            showErrors([
                "Tidak bisa terhubung ke server. Periksa koneksi internetmu lalu coba lagi.",
            ]);
        }

        setBusy(false);
    });

    // Tombol "kembali" dari halaman konfirmasi: muat ulang keranjang dari storage.
    window.addEventListener("pageshow", (e) => {
        if (e.persisted) {
            cart = loadCart();
            render();
        }
    });

    render();
    applyFilter();
})();

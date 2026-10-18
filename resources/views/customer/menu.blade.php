@extends('layouts.customer')

@section('title', 'Menu')

@section('header_actions')
    <button type="button" class="oj-cart-btn js-open-cart" aria-label="Buka pesanan">
        <i class="fas fa-shopping-bag" aria-hidden="true"></i>
        <span class="oj-badge js-cart-count" hidden>0</span>
    </button>
@endsection

@section('content')
    @php
        $placeholder = asset('assets/customer/img/placeholder.svg');
    @endphp

    <section class="oj-hero">
        <div class="container">
            <p class="oj-eyebrow mb-1">Selamat datang di Orenjoy</p>
            <h1>Mau makan apa hari ini?</h1>
            <p class="oj-hero-sub">
                @if (session('table_number'))
                    Pesanan akan diantar ke <strong>Meja {{ session('table_number') }}</strong>. Pilih menu, lalu kirim pesananmu dari HP.
                @else
                    Pilih menu favoritmu, lalu kirim pesanan langsung dari HP. Nomor meja bisa diisi saat memesan.
                @endif
            </p>
        </div>
    </section>

    <div class="oj-toolbar">
        <div class="container">
            <div class="oj-search">
                <label for="menuSearch" class="oj-sr">Cari menu</label>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input id="menuSearch" type="search" placeholder="Cari menu…" autocomplete="off">
            </div>

            <div class="oj-chips" role="group" aria-label="Filter kategori">
                <button type="button" class="oj-chip" data-filter="all" aria-pressed="true">Semua</button>
                @foreach ($categories as $category)
                    <button type="button" class="oj-chip" data-filter="{{ $category->slug }}" aria-pressed="false">{{ $category->cat_name }}</button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="container">
        <noscript>
            <p class="oj-alert mt-3">Aktifkan JavaScript di browser untuk bisa memesan dari halaman ini.</p>
        </noscript>

        <p id="menuStatus" class="oj-empty-msg" role="status"></p>

        @forelse ($categories as $category)
            <section class="oj-section" data-section="{{ $category->slug }}" aria-labelledby="cat-{{ $category->id }}">
                <h2 id="cat-{{ $category->id }}">{{ $category->cat_name }}</h2>

                <div class="row g-3">
                    @foreach ($category->items as $item)
                        <div class="col-6 col-md-4 col-lg-3 oj-col">
                            <article class="oj-card"
                                     data-item-id="{{ $item->id }}"
                                     data-title="{{ $item->name }}"
                                     data-price="{{ $item->price }}"
                                     data-img="{{ $item->image_url }}"
                                     data-search="{{ Str::lower($item->name.' '.$item->description) }}">
                                <div class="oj-card-img">
                                    <img src="{{ $item->image_url }}"
                                         alt="{{ $item->name }}"
                                         loading="lazy"
                                         onerror="this.onerror=null;this.src='{{ $placeholder }}'">
                                </div>

                                <div class="oj-card-body">
                                    <h3 class="oj-card-title">{{ $item->name }}</h3>

                                    @if ($item->description)
                                        <p class="oj-card-desc">{{ $item->description }}</p>
                                    @endif

                                    <div class="oj-card-foot">
                                        <span class="oj-price">Rp {{ number_format($item->price, 0, ',', '.') }}</span>

                                        <div>
                                            <button type="button" class="btn btn-primary oj-add" data-act="add" data-id="{{ $item->id }}"
                                                    aria-label="Tambah {{ $item->name }} ke pesanan">Tambah</button>

                                            <div class="oj-stepper js-stepper" role="group" aria-label="Jumlah {{ $item->name }}" hidden>
                                                <button type="button" data-act="dec" data-id="{{ $item->id }}" aria-label="Kurangi {{ $item->name }}">&minus;</button>
                                                <span class="js-qty" aria-live="polite">0</span>
                                                <button type="button" data-act="inc" data-id="{{ $item->id }}" aria-label="Tambah {{ $item->name }}">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="oj-empty-msg" style="display:block">Menu belum tersedia. Silakan kembali beberapa saat lagi.</p>
        @endforelse
    </div>

    {{-- Bar keranjang melayang --}}
    <div class="oj-cartbar" id="cartBar" hidden>
        <button type="button" class="oj-cartbar-btn js-open-cart">
            <span class="js-bar-count">0 item</span>
            <span class="js-bar-total">Rp 0</span>
            <span>Lihat pesanan <i class="fas fa-chevron-right" aria-hidden="true"></i></span>
        </button>
    </div>

    {{-- Keranjang + checkout --}}
    <div class="oj-backdrop" id="cartBackdrop"></div>

    <aside class="oj-drawer" id="cartDrawer" role="dialog" aria-modal="true" aria-labelledby="cartTitle" tabindex="-1">
        <div class="oj-drawer-head">
            <h2 id="cartTitle">Pesanan Anda</h2>
            <button type="button" class="oj-close js-close-cart" aria-label="Tutup pesanan">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <div class="oj-empty" id="cartEmpty" hidden>
            <i class="fas fa-shopping-bag" aria-hidden="true"></i>
            <p>Pesananmu masih kosong.<br>Pilih menu dulu, ya.</p>
            <button type="button" class="btn btn-primary js-close-cart">Lihat menu</button>
        </div>

        <form id="checkoutForm" class="oj-drawer-form">
            <div class="oj-drawer-body">
                <ul class="oj-lines" id="cartItems"></ul>

                <div class="oj-alert" id="formError" role="alert" tabindex="-1" hidden></div>

                <div class="oj-field">
                    <label for="customerName">Nama</label>
                    <input id="customerName" name="customer_name" type="text" maxlength="60" required
                           autocomplete="name" placeholder="Untuk memanggil pesananmu">
                </div>

                @if (session('table_number'))
                    <div class="oj-field">
                        <label>Nomor meja</label>
                        <p class="mb-0"><strong>Meja {{ session('table_number') }}</strong></p>
                        <small class="oj-muted">Ditentukan dari QR code di meja Anda.</small>
                    </div>
                @else
                    <div class="oj-alert" role="alert">
                        Scan QR code di meja Anda terlebih dahulu untuk bisa mengirim pesanan.
                    </div>
                @endif

                <fieldset class="oj-field">
                    <legend>Pembayaran</legend>
                    <div class="oj-pay">
                        <label>
                            <input type="radio" name="payment_method" value="tunai" checked>
                            <span>Tunai<small>Bayar di kasir</small></span>
                        </label>
                        <label>
                            <input type="radio" name="payment_method" value="qris">
                            <span>QRIS<small>Scan QRIS di kasir</small></span>
                        </label>
                    </div>
                </fieldset>

                <div class="oj-field">
                    <label for="orderNote">Catatan <span class="oj-opt">(opsional)</span></label>
                    <textarea id="orderNote" name="note" rows="2" maxlength="255"
                              placeholder="Misal: tidak pedas, tanpa es"></textarea>
                </div>
            </div>

            <div class="oj-drawer-foot">
                <dl class="oj-sums">
                    <div class="oj-sum"><dt>Subtotal</dt><dd class="js-subtotal">Rp 0</dd></div>
                    <div class="oj-sum"><dt class="js-tax-label">Pajak</dt><dd class="js-tax">Rp 0</dd></div>
                    <div class="oj-sum total"><dt>Total</dt><dd class="js-total">Rp 0</dd></div>
                </dl>

                <button type="submit" class="btn btn-primary oj-submit" id="submitBtn"
                        @disabled(! session('table_number'))>
                    <span class="js-submit-label">Kirim Pesanan</span>
                </button>
            </div>
        </form>
    </aside>
@endsection

@push('scripts')
    <script>
        window.ORENJOY = {
            taxPercent: @json((int) config('orenjoy.tax_percent', 10)),
            checkoutUrl: @json(route('checkout', [], false)),
            placeholder: @json($placeholder)
        };
    </script>
    <script src="{{ asset('assets/customer/js/cart.js') }}" defer></script>
@endpush

@extends('layouts.admin')

@section('title', 'Pesanan Baru')
@section('heading', 'Pesanan Baru')

@section('content')
    @php($oldType = old('type', 'dine_in'))

    <section class="section">
        <form action="{{ route('admin.orders.store') }}" method="POST" novalidate id="orderForm">
            @csrf

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Pesanan belum bisa disimpan:</strong>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-3">
                {{-- Kiri: cari dan pilih menu --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <label for="menuSearch" class="visually-hidden">Cari menu</label>
                            <input type="search" id="menuSearch" class="form-control mb-3" placeholder="Cari menu…" autocomplete="off">

                            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filter kategori">
                                <button type="button" class="btn btn-sm btn-primary js-chip" data-cat="all" aria-pressed="true">Semua</button>
                                @foreach ($categories as $category)
                                    <button type="button" class="btn btn-sm btn-outline-primary js-chip" data-cat="{{ $category->id }}" aria-pressed="false">{{ $category->cat_name }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @forelse ($categories as $category)
                        <div class="card js-cat" data-cat="{{ $category->id }}">
                            <div class="card-header"><h5 class="card-title mb-0">{{ $category->cat_name }}</h5></div>
                            <div class="card-body pt-0">
                                <ul class="list-group list-group-flush">
                                    @foreach ($category->items as $item)
                                        <li class="list-group-item d-flex align-items-center justify-content-between gap-3 px-0 js-item"
                                            data-name="{{ \Illuminate\Support\Str::lower($item->name) }}">
                                            <label for="qty-{{ $item->id }}" class="flex-grow-1 mb-0">
                                                <strong class="js-label">{{ $item->name }}</strong>
                                                <div class="text-muted small">Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                                            </label>
                                            <input type="number" id="qty-{{ $item->id }}" name="qty[{{ $item->id }}]"
                                                   class="form-control text-center js-qty" style="width: 90px"
                                                   min="0" max="50" step="1" inputmode="numeric"
                                                   data-price="{{ $item->price }}"
                                                   value="{{ old('qty.'.$item->id, 0) }}">
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @empty
                        <div class="card"><div class="card-body text-center text-muted">Belum ada menu aktif.</div></div>
                    @endforelse

                    <div class="card d-none" id="noResult">
                        <div class="card-body text-center text-muted">Tidak ada menu yang cocok.</div>
                    </div>
                </div>

                {{-- Kanan: data pesanan --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="form-label d-block">Menu dipilih</span>
                                <ul class="list-unstyled mb-0" id="picked" aria-live="polite"></ul>
                                <p class="text-muted small mb-0" id="pickedEmpty">Belum ada menu dipilih.</p>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Nama pelanggan</label>
                                <input type="text" id="customer_name" name="customer_name" maxlength="60" required
                                       value="{{ old('customer_name') }}"
                                       class="form-control @error('customer_name') is-invalid @enderror">
                            </div>

                            <div class="mb-3">
                                <span class="form-label d-block">Jenis pesanan</span>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input js-type" type="radio" name="type" id="type-dine" value="dine_in"
                                           @checked($oldType === 'dine_in')>
                                    <label class="form-check-label" for="type-dine">Makan di tempat</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input js-type" type="radio" name="type" id="type-take" value="takeaway"
                                           @checked($oldType === 'takeaway')>
                                    <label class="form-check-label" for="type-take">Bawa pulang</label>
                                </div>
                            </div>

                            <div class="mb-3" id="tableField">
                                <label for="table_id" class="form-label">Meja</label>

                                <div class="d-flex flex-wrap gap-1 mb-1" role="group" aria-label="Status meja">
                                    @foreach ($tables as $table)
                                        @php($occ = $occupancy[$table->number] ?? null)
                                        <button type="button"
                                                class="btn btn-sm js-table-chip {{ $occ ? 'btn-warning' : 'btn-outline-success' }}"
                                                data-table-id="{{ $table->id }}"
                                                title="{{ $occ ? 'Terisi: '.$occ['guests'] : 'Kosong' }}">{{ $table->number }}</button>
                                    @endforeach
                                </div>
                                <div class="form-text mb-2">Hijau = kosong. Kuning = ada pesanan dalam {{ $window }} menit terakhir.</div>

                                <select id="table_id" name="table_id" class="form-select @error('table_id') is-invalid @enderror">
                                    <option value="">Pilih meja</option>
                                    @foreach ($tables as $table)
                                        @php($occ = $occupancy[$table->number] ?? null)
                                        <option value="{{ $table->id }}"
                                                data-occupied="{{ $occ ? '1' : '0' }}"
                                                data-guests="{{ $occ['guests'] ?? '' }}"
                                                @selected((int) old('table_id') === $table->id)>
                                            Meja {{ $table->number }}{{ $occ ? ' (terisi)' : '' }}
                                        </option>
                                    @endforeach
                                </select>

                                <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small d-none" id="tableHint" role="status"></div>
                            </div>

                            <div class="mb-3">
                                <span class="form-label d-block">Pembayaran</span>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pay-tunai" value="tunai"
                                           @checked(old('payment_method', 'tunai') === 'tunai')>
                                    <label class="form-check-label" for="pay-tunai">Tunai</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pay-qris" value="qris"
                                           @checked(old('payment_method') === 'qris')>
                                    <label class="form-check-label" for="pay-qris">QRIS</label>
                                </div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="paid" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="paid" name="paid" value="1"
                                       @checked(old('paid', '1') === '1')>
                                <label class="form-check-label" for="paid">Pembayaran sudah diterima sekarang</label>
                                <div class="form-text">Kalau dicentang, pesanan langsung masuk antrean dapur.</div>
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                                <textarea id="note" name="note" rows="2" maxlength="255"
                                          class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                            </div>

                            <dl class="mb-3">
                                <div class="d-flex justify-content-between"><dt class="fw-normal">Subtotal</dt><dd class="mb-0" id="sumSub">Rp 0</dd></div>
                                <div class="d-flex justify-content-between"><dt class="fw-normal">Pajak</dt><dd class="mb-0" id="sumTax">Rp 0</dd></div>
                                <div class="d-flex justify-content-between"><dt>Total</dt><dd class="mb-0 fw-bold" id="sumTotal">Rp 0</dd></div>
                                <div class="form-text">Perkiraan. Angka akhir dihitung ulang oleh server.</div>
                            </dl>

                            <button type="submit" class="btn btn-primary w-100">Simpan pesanan</button>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-light w-100 mt-2">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            var taxPercent = @json((int) config('orenjoy.tax_percent', 10));
            var fmt = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };

            var qtyInputs = document.querySelectorAll('.js-qty');
            var tableField = document.getElementById('tableField');
            var tableSelect = document.getElementById('table_id');
            var search = document.getElementById('menuSearch');
            var chips = document.querySelectorAll('.js-chip');
            var cats = document.querySelectorAll('.js-cat');
            var noResult = document.getElementById('noResult');
            var picked = document.getElementById('picked');
            var pickedEmpty = document.getElementById('pickedEmpty');
            var activeCat = 'all';
            var tableChips = document.querySelectorAll('.js-table-chip');
            var tableHint = document.getElementById('tableHint');
            var form = document.getElementById('orderForm');

            function qtyOf(input) {
                return Math.max(0, Math.min(50, parseInt(input.value, 10) || 0));
            }

            // Ringkasan, daftar "Menu dipilih", dan total.
            function recalc() {
                var sub = 0, tax = 0, rows = [];

                qtyInputs.forEach(function (input) {
                    var qty = qtyOf(input);
                    if (qty < 1) { return; }

                    var line = qty * Number(input.dataset.price);
                    sub += line;
                    tax += Math.round(line * taxPercent / 100);

                    var label = input.closest('.js-item').querySelector('.js-label').textContent;
                    rows.push({ label: label, qty: qty });
                });

                picked.innerHTML = '';
                rows.forEach(function (row) {
                    var li = document.createElement('li');
                    li.className = 'd-flex justify-content-between';
                    var name = document.createElement('span');
                    name.textContent = row.label;
                    var qty = document.createElement('strong');
                    qty.textContent = '× ' + row.qty;
                    li.appendChild(name);
                    li.appendChild(qty);
                    picked.appendChild(li);
                });
                pickedEmpty.classList.toggle('d-none', rows.length > 0);

                document.getElementById('sumSub').textContent = fmt(sub);
                document.getElementById('sumTax').textContent = fmt(tax);
                document.getElementById('sumTotal').textContent = fmt(sub + tax);
            }

            // Cari menu dan filter kategori. Menu yang disembunyikan tetap ikut terkirim bila jumlahnya > 0.
            function applyFilter() {
                var q = search.value.trim().toLowerCase();
                var shown = 0;

                cats.forEach(function (card) {
                    var catOk = activeCat === 'all' || card.dataset.cat === activeCat;
                    var any = false;

                    card.querySelectorAll('.js-item').forEach(function (li) {
                        var ok = catOk && (q === '' || li.dataset.name.indexOf(q) !== -1);
                        li.classList.toggle('d-none', !ok);
                        if (ok) { any = true; shown++; }
                    });

                    card.classList.toggle('d-none', !any);
                });

                noResult.classList.toggle('d-none', shown > 0 || cats.length === 0);
            }

            function selectedTable() {
                return tableSelect.disabled ? null : tableSelect.options[tableSelect.selectedIndex];
            }

            // Tandai meja terpilih dan beri peringatan kalau mejanya sudah ada pesanan.
            function syncTable() {
                tableChips.forEach(function (chip) {
                    chip.classList.toggle('active', chip.dataset.tableId === tableSelect.value);
                });

                var opt = selectedTable();
                var busy = opt && opt.value && opt.dataset.occupied === '1';
                tableHint.classList.toggle('d-none', !busy);
                tableHint.textContent = busy
                    ? 'Meja ini sudah ada pesanan atas nama: ' + opt.dataset.guests + '. Pastikan pelanggan baru memang boleh duduk di sini.'
                    : '';
            }

            function syncType() {
                var dineIn = document.getElementById('type-dine').checked;
                tableField.hidden = !dineIn;
                tableSelect.disabled = !dineIn;
            }

            qtyInputs.forEach(function (input) { input.addEventListener('input', recalc); });
            document.querySelectorAll('.js-type').forEach(function (input) { input.addEventListener('change', syncType); });
            search.addEventListener('input', applyFilter);

            tableSelect.addEventListener('change', syncTable);
            tableChips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    tableSelect.value = chip.dataset.tableId;
                    syncTable();
                });
            });

            // Minta kasir memastikan dulu sebelum menyimpan pesanan di meja yang sudah terisi.
            form.addEventListener('submit', function (e) {
                var opt = selectedTable();
                if (opt && opt.value && opt.dataset.occupied === '1') {
                    var ok = window.confirm('Meja ini sudah ada pesanan atas nama ' + opt.dataset.guests + '. Tetap simpan di meja ini?');
                    if (!ok) { e.preventDefault(); }
                }
            });

            // Enter di kolom cari tidak boleh mengirim form.
            search.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); }
            });

            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    activeCat = chip.dataset.cat;
                    chips.forEach(function (c) {
                        var on = c === chip;
                        c.classList.toggle('btn-primary', on);
                        c.classList.toggle('btn-outline-primary', !on);
                        c.setAttribute('aria-pressed', on ? 'true' : 'false');
                    });
                    applyFilter();
                });
            });

            recalc();
            syncType();
            syncTable();
            applyFilter();
        })();
    </script>
@endpush

@extends('layouts.admin')

@php
    use App\Support\OrderFlow;

    $isChef = $role === 'chef';
    $title = $isChef ? 'Antrean Dapur' : 'Kelola Pesanan';
    $paymentText = ['tunai' => 'Tunai', 'qris' => 'QRIS'];
    $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
@endphp

@section('title', $title)
@section('heading', $title)

@section('content')
    <section class="section">
        <ul class="nav nav-pills mb-3 flex-wrap gap-1">
            <li class="nav-item">
                <a class="nav-link {{ $filter === 'aktif' ? 'active' : '' }}"
                   href="{{ route('admin.orders.index') }}">
                    Aktif @if ($activeCount > 0)<span class="badge bg-light text-dark ms-1">{{ $activeCount }}</span>@endif
                </a>
            </li>
            @foreach ($visible as $status)
                <li class="nav-item">
                    <a class="nav-link {{ $filter === $status ? 'active' : '' }}"
                       href="{{ route('admin.orders.index', ['status' => $status]) }}">
                        {{ OrderFlow::label($status) }}
                        @if (($counts[$status] ?? 0) > 0)<span class="badge bg-light text-dark ms-1">{{ $counts[$status] }}</span>@endif
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Bagian ini diganti otomatis tiap 20 detik oleh skrip di bawah --}}
        <div id="orders-board">
            @if ($orders->isEmpty())
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        @if ($filter === 'aktif')
                            Belum ada pesanan yang perlu ditangani.
                        @else
                            Tidak ada pesanan dengan status ini.
                        @endif
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($orders as $order)
                        @php($targets = OrderFlow::targets($order->status, $role))
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 mb-0">
                                <div class="card-header d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <strong>{{ $order->order_code }}</strong>
                                        <div class="text-muted small">
                                            Meja {{ $order->table_number }}
                                            @if ($order->customer_name) &middot; {{ $order->customer_name }} @endif
                                        </div>
                                        <div class="text-muted small">
                                            {{ $order->created_at->format('H:i') }} &middot; {{ $order->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <span class="badge {{ OrderFlow::badge($order->status) }}">{{ OrderFlow::label($order->status) }}</span>
                                </div>

                                <div class="card-body">
                                    <ul class="list-unstyled mb-2">
                                        @foreach ($order->orderItems as $line)
                                            <li class="d-flex justify-content-between">
                                                <span>{{ $line->item?->name ?? 'Menu' }}</span>
                                                <strong>&times; {{ $line->quantity }}</strong>
                                            </li>
                                        @endforeach
                                    </ul>

                                    @if ($order->note)
                                        <div class="alert alert-light-warning py-2 px-3 mb-2 small">
                                            <strong>Catatan:</strong> {{ $order->note }}
                                        </div>
                                    @endif

                                    @unless ($isChef)
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>{{ $paymentText[$order->payment_method] ?? $order->payment_method }}</span>
                                            <span>Total <strong class="text-body">{{ $rupiah($order->grand_total) }}</strong></span>
                                        </div>
                                    @endunless
                                </div>

                                @if (count($targets) > 0)
                                    <div class="card-footer d-flex flex-wrap gap-2">
                                        @foreach ($targets as $target)
                                            @php([$actionLabel, $actionClass, $needConfirm] = OrderFlow::action($target))
                                            <form action="{{ route('admin.orders.status', $order) }}" method="POST"
                                                  @if ($needConfirm) onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?')" @endif>
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $target }}">
                                                <button type="submit" class="btn btn-sm {{ $actionClass }}">{{ $actionLabel }}</button>
                                            </form>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($orders->hasPages())
                    <div class="mt-3">{{ $orders->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        // Perbarui daftar pesanan tiap 20 detik tanpa memuat ulang seluruh halaman.
        (function () {
            var board = function () { return document.getElementById('orders-board'); };

            setInterval(function () {
                if (document.hidden || !board()) { return; }

                fetch(window.location.href, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) { return res.ok ? res.text() : null; })
                    .then(function (html) {
                        if (!html) { return; }
                        var fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('orders-board');
                        if (fresh && board()) { board().replaceWith(fresh); }
                    })
                    .catch(function () { /* koneksi putus: coba lagi di putaran berikutnya */ });
            }, 20000);
        })();
    </script>
@endpush

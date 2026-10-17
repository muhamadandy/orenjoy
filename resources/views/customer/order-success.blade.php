@extends('layouts.customer')

@section('title', 'Pesanan '.$order->order_code)

@section('content')
    @php
        $statuses = [
            'pending' => ['Menunggu pembayaran', 'is-pending'],
            'settlement' => ['Pembayaran diterima, pesanan diproses', 'is-progress'],
            'cooked' => ['Siap disajikan', 'is-ready'],
            'completed' => ['Selesai', 'is-done'],
            'cancelled' => ['Dibatalkan', 'is-cancelled'],
        ];
        [$statusText, $statusClass] = $statuses[$order->status] ?? [ucfirst($order->status), 'is-pending'];

        $paymentText = ['tunai' => 'Tunai', 'qris' => 'QRIS'][$order->payment_method] ?? $order->payment_method;
        $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $isActive = in_array($order->status, ['pending', 'settlement', 'cooked'], true);
    @endphp

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="oj-success">
                    <div class="oj-success-icon" aria-hidden="true"><i class="fas fa-check"></i></div>
                    <h1>Pesanan terkirim!</h1>
                    <p class="oj-muted mb-3">Tunjukkan kode ini ke kasir.</p>

                    <p class="oj-code">{{ $order->order_code }}</p>
                    <p><span class="oj-status {{ $statusClass }}">{{ $statusText }}</span></p>

                    @if ($order->status === 'pending')
                        <p class="oj-muted">Pembayaran {{ $paymentText }} dilakukan di kasir. Pesananmu mulai diproses setelah pembayaran diterima.</p>
                    @endif
                </div>

                <div class="oj-panel">
                    <dl class="oj-meta">
                        <div><dt>Nama</dt><dd>{{ $order->customer_name }}</dd></div>
                        <div><dt>Meja</dt><dd>{{ $order->table_number }}</dd></div>
                        <div><dt>Pembayaran</dt><dd>{{ $paymentText }}</dd></div>
                    </dl>

                    <h2 class="oj-panel-title">Rincian pesanan</h2>
                    <ul class="oj-lines">
                        @foreach ($order->orderItems as $line)
                            <li class="oj-line">
                                <div class="oj-line-info">
                                    <div class="oj-line-name">{{ $line->item?->name ?? 'Menu' }}</div>
                                    <div class="oj-line-price">{{ $rupiah($line->price) }} &times; {{ $line->quantity }}</div>
                                </div>
                                <strong>{{ $rupiah($line->price * $line->quantity) }}</strong>
                            </li>
                        @endforeach
                    </ul>

                    @if ($order->note)
                        <p class="oj-note"><strong>Catatan:</strong> {{ $order->note }}</p>
                    @endif

                    <dl class="oj-sums">
                        <div class="oj-sum"><dt>Subtotal</dt><dd>{{ $rupiah($order->sub_total) }}</dd></div>
                        <div class="oj-sum"><dt>Pajak</dt><dd>{{ $rupiah($order->tax) }}</dd></div>
                        <div class="oj-sum total"><dt>Total</dt><dd>{{ $rupiah($order->grand_total) }}</dd></div>
                    </dl>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('menu') }}" class="btn btn-primary oj-submit">Pesan lagi</a>
                    @if ($isActive)
                        <p class="oj-muted mt-3 mb-0"><small>Status diperbarui otomatis setiap 30 detik.</small></p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        @if (in_array($order->status, ['pending', 'settlement', 'cooked'], true))
        setTimeout(function () { window.location.reload(); }, 30000);
        @endif
    </script>
@endpush

@php
    $latestPayment = $order->payments()->latest('id')->first();
    $paymentsEnabled = app(\App\Services\PaymentService::class)->enabled();
    $snapJs = app(\App\Services\PaymentService::class)->snapJsUrl();
    $canPay = $latestPayment && $latestPayment->status === 'pending' && $latestPayment->snap_token;
@endphp

@if ($order->payment_method === 'qris' && $order->status === 'pending')
    <div class="oj-panel text-center" id="qris-box"
         data-status-url="{{ route('order.payment.status', [$order->order_code], false) }}"
         data-order-status="{{ $order->status }}"
         data-payment-status="{{ $latestPayment?->status }}"
         data-token="{{ $canPay ? $latestPayment->snap_token : '' }}">

        @if (session('payment_error'))
            <div class="alert alert-danger">{{ session('payment_error') }}</div>
        @endif

        @if ($paymentsEnabled && $canPay)
            <h2 class="oj-panel-title mb-1">Bayar dengan QRIS</h2>
            <p class="oj-muted mb-3">Total Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
            <button type="button" class="btn btn-primary oj-submit" id="pay-button">Bayar sekarang</button>
            <p class="oj-muted mt-3 mb-0"><small>Halaman ini berubah sendiri setelah pembayaran berhasil.</small></p>

            <script src="{{ $snapJs }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
        @elseif ($paymentsEnabled)
            <p class="mb-2">{{ $latestPayment ? 'Pembayaran sebelumnya sudah tidak berlaku.' : 'Halaman pembayaran belum dibuat.' }}</p>
            <form method="POST" action="{{ route('order.payment.qris', [$order->order_code], false) }}">
                @csrf
                <button class="btn btn-primary oj-submit">Buat pembayaran baru</button>
            </form>
            <p class="oj-muted mt-3 mb-0"><small>Atau bayar langsung di kasir.</small></p>
        @else
            <p class="mb-0">Silakan bayar QRIS di kasir.</p>
        @endif
    </div>

    @if ($paymentsEnabled)
        <script>
            (function () {
                var box = document.getElementById('qris-box');
                if (!box) return;

                var button = document.getElementById('pay-button');
                var token = box.dataset.token;

                function openSnap() {
                    if (!window.snap || !token) return;
                    window.snap.pay(token, {
                        onSuccess: function () { location.reload(); },
                        onPending: function () {},
                        onError: function () { location.reload(); },
                        onClose: function () {}
                    });
                }

                if (button) {
                    button.addEventListener('click', openSnap);

                    // Buka otomatis satu kali saat pelanggan pertama tiba di halaman ini.
                    try {
                        var key = 'oj-snap-' + token;
                        if (!sessionStorage.getItem(key)) {
                            sessionStorage.setItem(key, '1');
                            window.addEventListener('load', openSnap);
                        }
                    } catch (e) {}
                }

                var timer = setInterval(function () {
                    fetch(box.dataset.statusUrl, {headers: {'Accept': 'application/json'}})
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (d) {
                            if (!d) return;
                            if (d.order_status !== box.dataset.orderStatus ||
                                (d.payment_status || '') !== (box.dataset.paymentStatus || '')) {
                                clearInterval(timer);
                                location.reload();
                            }
                        })
                        .catch(function () {});
                }, 5000);
            })();
        </script>
    @endif
@endif

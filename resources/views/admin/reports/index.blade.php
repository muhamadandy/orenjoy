@extends('layouts.admin')

@php
    $rupiah = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $paymentLabels = ['tunai' => 'Tunai', 'qris' => 'QRIS'];
    $query = ['from' => $from, 'to' => $to];
@endphp

@section('title', 'Laporan Penjualan')
@section('heading', 'Laporan Penjualan')

@section('content')
    <section class="section">
        {{-- Pilih rentang tanggal --}}
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
                    <div class="col-sm-6 col-md-3">
                        <label for="from" class="form-label">Dari tanggal</label>
                        <input type="date" id="from" name="from" value="{{ $from }}" class="form-control">
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label for="to" class="form-label">Sampai tanggal</label>
                        <input type="date" id="to" name="to" value="{{ $to }}" class="form-control">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.reports.export', $query) }}" class="btn btn-outline-primary">
                            <i class="bi bi-download"></i> Unduh CSV
                        </a>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    @foreach ($presets as $label => [$presetFrom, $presetTo])
                        <a href="{{ route('admin.reports.index', ['from' => $presetFrom, 'to' => $presetTo]) }}"
                           class="btn btn-sm {{ $from === $presetFrom && $to === $presetTo ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                    @endforeach
                </div>

                @if ($rangeError)
                    <div class="alert alert-warning mt-3 mb-0" role="alert">{{ $rangeError }}</div>
                @endif
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0"><div class="card-body">
                    <div class="text-muted small">Total penjualan</div>
                    <div class="fs-4 fw-bold">{{ $rupiah($summary->total) }}</div>
                    <div class="text-muted small">termasuk pajak</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0"><div class="card-body">
                    <div class="text-muted small">Jumlah pesanan</div>
                    <div class="fs-4 fw-bold">{{ number_format((int) $summary->orders, 0, ',', '.') }}</div>
                    <div class="text-muted small">{{ $cancelled }} dibatalkan (tidak dihitung)</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0"><div class="card-body">
                    <div class="text-muted small">Penjualan sebelum pajak</div>
                    <div class="fs-4 fw-bold">{{ $rupiah($summary->sub_total) }}</div>
                    <div class="text-muted small">Pajak {{ $rupiah($summary->tax) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0"><div class="card-body">
                    <div class="text-muted small">Rata-rata per pesanan</div>
                    <div class="fs-4 fw-bold">{{ $rupiah($average) }}</div>
                    <div class="text-muted small">
                        @foreach ($paymentLabels as $key => $label)
                            {{ $label }} {{ $rupiah($byPayment[$key]->total ?? 0) }}@if (! $loop->last) &middot; @endif
                        @endforeach
                    </div>
                </div></div>
            </div>
        </div>

        {{-- Grafik --}}
        <div class="card mt-3">
            <div class="card-header"><h5 class="card-title mb-0">Penjualan per hari</h5></div>
            <div class="card-body">
                <div id="chart-report"></div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Menu terlaris --}}
            <div class="col-lg-5">
                <div class="card h-100 mb-0">
                    <div class="card-header"><h5 class="card-title mb-0">Menu terlaris</h5></div>
                    <div class="card-body">
                        @if ($topItems->isEmpty())
                            <p class="text-muted mb-0">Belum ada penjualan pada rentang ini.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr><th>#</th><th>Menu</th><th class="text-end">Terjual</th><th class="text-end">Nilai</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topItems as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row->name }}</td>
                                                <td class="text-end">{{ number_format((int) $row->qty, 0, ',', '.') }}</td>
                                                <td class="text-end">{{ $rupiah($row->revenue) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-text">Nilai dihitung sebelum pajak.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Rincian harian --}}
            <div class="col-lg-7">
                <div class="card h-100 mb-0">
                    <div class="card-header"><h5 class="card-title mb-0">Rincian harian</h5></div>
                    <div class="card-body">
                        @if ($daily->isEmpty())
                            <p class="text-muted mb-0">Belum ada penjualan pada rentang ini.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th class="text-end">Pesanan</th>
                                            <th class="text-end">Sebelum pajak</th>
                                            <th class="text-end">Pajak</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($daily as $day => $row)
                                            <tr>
                                                <td>{{ \Illuminate\Support\Carbon::parse($day)->format('d/m/Y') }}</td>
                                                <td class="text-end">{{ (int) $row->orders }}</td>
                                                <td class="text-end">{{ $rupiah($row->sub_total) }}</td>
                                                <td class="text-end">{{ $rupiah($row->tax) }}</td>
                                                <td class="text-end fw-bold">{{ $rupiah($row->total) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td>Total</td>
                                            <td class="text-end">{{ (int) $summary->orders }}</td>
                                            <td class="text-end">{{ $rupiah($summary->sub_total) }}</td>
                                            <td class="text-end">{{ $rupiah($summary->tax) }}</td>
                                            <td class="text-end">{{ $rupiah($summary->total) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Yang dihitung: pesanan yang sudah dibayar (diproses, siap saji, atau selesai), berdasarkan tanggal pesanan dibuat.
        </p>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/admin/extensions/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            var data = @json($chart);
            var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            var rupiah = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };

            new ApexCharts(document.querySelector('#chart-report'), {
                chart: { type: 'bar', height: 300, toolbar: { show: false }, background: 'transparent' },
                theme: { mode: dark ? 'dark' : 'light' },
                series: [{ name: 'Penjualan', data: data.totals }],
                xaxis: { categories: data.labels, tickAmount: Math.min(data.labels.length, 12) },
                yaxis: { labels: { formatter: function (v) { return rupiah(v); } } },
                dataLabels: { enabled: false },
                plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
                tooltip: { y: { formatter: rupiah } },
                noData: { text: 'Belum ada data' }
            }).render();
        })();
    </script>
@endpush

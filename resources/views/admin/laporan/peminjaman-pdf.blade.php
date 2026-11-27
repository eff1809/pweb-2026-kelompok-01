<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Peminjaman Ruang dan Alat Laboratorium</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 16px;
        }
        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 15px;
            margin: 0 0 2px 0;
            color: #1e3a8a;
        }
        .header p {
            margin: 0;
            font-size: 10px;
            color: #4b5563;
        }
        .meta {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .meta td {
            padding: 2px 6px 2px 0;
            vertical-align: top;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .summary td {
            border: 1px solid #d1d5db;
            padding: 5px 8px;
            text-align: center;
            background-color: #f9fafb;
        }
        .summary .angka {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
        }
        .summary .label {
            font-size: 8.5px;
            color: #4b5563;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th {
            background-color: #1e3a8a;
            color: #ffffff;
            text-align: left;
            padding: 6px;
            border: 1px solid #1e3a8a;
            font-size: 9.5px;
        }
        table.data td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            vertical-align: top;
        }
        table.data tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .status-diajukan { background-color: #fef3c7; color: #92400e; }
        .status-disetujui { background-color: #dbeafe; color: #1e40af; }
        .status-ditolak { background-color: #fee2e2; color: #991b1b; }
        .status-dipinjam { background-color: #e0e7ff; color: #3730a3; }
        .status-dikembalikan { background-color: #dcfce7; color: #166534; }
        .status-dibatalkan { background-color: #f3f4f6; color: #374151; }
        .empty {
            text-align: center;
            padding: 16px;
            color: #6b7280;
            font-style: italic;
        }
        .footer {
            margin-top: 14px;
            font-size: 8.5px;
            color: #6b7280;
            text-align: right;
        }
        ul.alat-list {
            margin: 0;
            padding-left: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rekap Peminjaman Ruang dan Alat Laboratorium</h1>
        <p>Program Studi Teknik Informatika — Universitas Hasanuddin</p>
    </div>

    <table class="meta">
        <tr>
            <td style="width: 14%;"><strong>Periode</strong></td>
            <td style="width: 36%;">
                : {{ $filter['dari'] ?? 'Awal' }} s.d. {{ $filter['sampai'] ?? 'Sekarang' }}
            </td>
            <td style="width: 14%;"><strong>Dicetak oleh</strong></td>
            <td style="width: 36%;">: {{ $dicetakOleh->name }}</td>
        </tr>
        <tr>
            <td><strong>Filter Status</strong></td>
            <td>: {{ $filter['status'] ? ($daftarStatus[$filter['status']] ?? $filter['status']) : 'Semua Status' }}</td>
            <td><strong>Waktu Cetak</strong></td>
            <td>: {{ $dicetakPada->format('d/m/Y H:i') }} WITA</td>
        </tr>
        @if ($filter['q'] !== '')
            <tr>
                <td><strong>Kata Kunci</strong></td>
                <td colspan="3">: "{{ $filter['q'] }}"</td>
            </tr>
        @endif
    </table>

    <table class="summary">
        <tr>
            <td>
                <div class="angka">{{ $ringkasan['total'] }}</div>
                <div class="label">Total Pengajuan</div>
            </td>
            @foreach ($ringkasan['per_status'] as $item)
                <td>
                    <div class="angka">{{ $item['jumlah'] }}</div>
                    <div class="label">{{ $item['label'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 12%;">Tanggal &amp; Jam</th>
                <th style="width: 16%;">Peminjam</th>
                <th style="width: 14%;">Ruang</th>
                <th style="width: 18%;">Alat (Jumlah)</th>
                <th style="width: 17%;">Keperluan</th>
                <th style="width: 9%;">Status</th>
                <th style="width: 11%;">Catatan Admin</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($peminjaman as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->tanggal->format('d/m/Y') }}</strong><br>
                        {{ substr($item->jam_mulai, 0, 5) }}–{{ substr($item->jam_selesai, 0, 5) }}
                    </td>
                    <td>
                        {{ $item->user?->name ?? '-' }}<br>
                        <span style="color: #6b7280;">{{ $item->user?->nim ?? '-' }}</span>
                    </td>
                    <td>{{ $item->ruang?->nama ?? '-' }}</td>
                    <td>
                        @if ($item->alat->isEmpty())
                            -
                        @else
                            <ul class="alat-list">
                                @foreach ($item->alat as $alat)
                                    <li>{{ $alat->nama }} ({{ $alat->pivot->jumlah }} unit)</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                    <td>{{ $item->keperluan }}</td>
                    <td>
                        <span class="badge status-{{ $item->status }}">
                            {{ $item->labelStatus() }}
                        </span>
                    </td>
                    <td>{{ $item->catatan_admin ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Tidak ada data peminjaman yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem Peminjaman Ruang &amp; Alat Laboratorium — Kelompok 01.
    </div>
</body>
</html>

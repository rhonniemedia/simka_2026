<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 15mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 9pt;
            text-align: center;
        }

        h4 {
            font-size: 12pt;
            line-height: 1.25;
            margin: 0 0 2mm 0;
            padding: 0;
            text-transform: uppercase;
        }

        .meta {
            font-size: 8pt;
            margin-bottom: 4mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 0.3px solid black;
            padding: 4.5px;
            text-align: left;
            vertical-align: top;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        small {
            font-size: 7.5pt;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <h4>
        {{ $title }}<br>
        PERIODE {{ $periode }}<br>
        @if ($statusLabel)
        STATUS: {{ $statusLabel }}<br>
        @endif
        {{ $school }}
    </h4>
    <div class="meta">Per {{ $printedAt }}</div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 4%;">NO</th>
                <th style="width: 20%;">
                    Pegawai<br>
                    <small>Nama | Nomor Induk Pegawai</small>
                </th>
                <th style="width: 12%;">
                    Tanggal Lahir<br>
                    <small>Tanggal | Usia</small>
                </th>
                <th style="width: 9%;">Status Kepegawaian</th>
                <th style="width: 17%;">Jabatan ASN</th>
                <th style="width: 11%;">Nomor Telepon</th>
                <th style="width: 13%;">
                    Pensiun<br>
                    <small>TMT | Batas Usia</small>
                </th>
                <th style="width: 14%;">
                    Keterangan<br>
                    <small>Status | Catatan</small>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>
                    <b>{{ $row['name'] }}</b><br>
                    <small>{{ $row['nip'] }}</small>
                </td>
                <td>
                    {{ $row['dob'] }}<br>
                    <small>{{ $row['age'] ?? '-' }}</small>
                </td>
                <td>{{ $row['employment'] }}</td>
                <td>{{ $row['position'] }}</td>
                <td>{{ $row['phone'] }}</td>
                <td>
                    {{ $row['tmt'] }}<br>
                    <small>Batas <b>{{ $row['limit_age'] ?? '-' }}</b> tahun</small>
                </td>
                <td>
                    <b>{{ $row['status'] }}</b>
                    @if ($row['note'])
                    <br><small>{{ $row['note'] }}</small>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Tidak ada data.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
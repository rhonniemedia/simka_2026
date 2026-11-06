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
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }

        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            line-height: 1.3;
            text-transform: uppercase;
            margin: 0 0 1mm 0;
        }

        .meta {
            text-align: center;
            font-size: 9pt;
            margin-bottom: 5mm;
        }

        .group {
            page-break-before: always;
        }

        .group.first {
            page-break-before: avoid;
        }

        .info {
            border-collapse: collapse;
            margin-bottom: 3mm;
        }

        .info td {
            border: none;
            padding: 1px 6px 1px 0;
        }

        table.body {
            width: 100%;
            border-collapse: collapse;
        }

        table.body th,
        table.body td {
            border: 0.5px solid black;
            padding: 4px 8px;
            vertical-align: top;
        }

        table.body th {
            background-color: #f2f2f2;
        }

        table.body thead {
            display: table-header-group;
        }

        table.body tr {
            page-break-inside: avoid;
        }

        .center {
            text-align: center;
        }

        .count {
            margin-top: 3mm;
            font-size: 9pt;
        }
    </style>
</head>

<body>
    <div class="title">
        {{ $title }}<br>
        {{ $school }}
    </div>
    <div class="meta">
        Per {{ $printedAt }}
        @if ($filterLabel)
        <br>{{ $filterLabel }}
        @endif
    </div>

    @forelse ($groups as $group)
    <div class="group {{ $loop->first ? 'first' : '' }}">
        <table class="info">
            <tr>
                <td>Jenis Pendidik Tenaga Kependidikan (PTK)</td>
                <td>:</td>
                <td>{{ $group['ptk'] }}</td>
            </tr>
            <tr>
                <td>Jenis Pegawai</td>
                <td>:</td>
                <td>{{ $group['employment'] }}</td>
            </tr>
            <tr>
                <td>Tahun</td>
                <td>:</td>
                <td>{{ $year }}</td>
            </tr>
        </table>

        <table class="body">
            <thead>
                <tr>
                    <th class="center" style="width: 5%;">NO</th>
                    <th style="width: 45%;">NAMA PEGAWAI/NIP</th>
                    <th class="center" style="width: 6%;">JK</th>
                    <th class="center" style="width: 9%;">GOL</th>
                    <th style="width: 35%;">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $row)
                <tr>
                    <td class="center">{{ $row['no'] }}</td>
                    <td>{{ $row['name'] }}<br>{{ $row['nip'] }}</td>
                    <td class="center">{{ $row['gender'] }}</td>
                    <td class="center">{{ $row['grade'] }}</td>
                    <td>{{ $row['note'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="count">Jumlah pegawai: <b>{{ count($group['rows']) }}</b></div>
    </div>
    @empty
    <p class="center">Tidak ada data pegawai.</p>
    @endforelse

    @if (count($groups) > 1)
    <div class="count" style="margin-top: 6mm;">Jumlah seluruh pegawai: <b>{{ $total }}</b></div>
    @endif
</body>

</html>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 12mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 8pt;
        }

        .header {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.3;
            margin: 0 0 1mm 0;
            text-transform: uppercase;
        }

        .meta {
            text-align: center;
            font-size: 8pt;
            margin-bottom: 4mm;
        }

        .tg {
            width: 100%;
            border-collapse: collapse;
        }

        .tg th,
        .tg td {
            border: 0.5px solid black;
            padding: 4px;
            vertical-align: top;
            text-align: left;
        }

        .tg th {
            text-align: center;
            vertical-align: middle;
            font-size: 7.5pt;
        }

        .tg thead {
            display: table-header-group;
        }

        .tg tr {
            page-break-inside: avoid;
        }

        .center {
            text-align: center !important;
        }

        .sign {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8mm;
            page-break-inside: avoid;
        }

        .sign td {
            border: none;
            font-size: 10pt;
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="header">
        {{ $title }}<br>
        {{ $school }}
    </div>
    <div class="meta">Per {{ $printedAt }}</div>

    <table class="tg">
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">NO<br>URUT</th>
                <th rowspan="2" style="width: 13%;">NAMA LENGKAP<br>NOMOR INDUK PEGAWAI</th>
                <th rowspan="2" style="width: 10%;">TEMPAT DAN<br>TANGGAL LAHIR</th>
                <th colspan="2">KEPANGKATAN</th>
                <th rowspan="2" style="width: 10%;">JABATAN<br>TMT</th>
                <th colspan="2">MASA KERJA</th>
                <th colspan="3">PENDIDIKAN</th>
                <th rowspan="2" style="width: 6%;">MUTASI<br>MASUK</th>
                <th rowspan="2" style="width: 6.5%;">PENSIUN</th>
            </tr>
            <tr>
                <th style="width: 9%;">PANGKAT/GOL</th>
                <th style="width: 7%;">TMT</th>
                <th style="width: 6%;">GOLONGAN</th>
                <th style="width: 6%;">SELURUH</th>
                <th style="width: 8.5%;">SATUAN PENDIDIKAN</th>
                <th style="width: 6.5%;">LULUS</th>
                <th style="width: 8.5%;">TINGKAT/JURUSAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
            <tr>
                <td class="center">{{ $row['no'] }}</td>
                <td>{{ $row['name'] }}<br>{{ $row['nip'] }}</td>
                <td>{{ $row['birth_place'] }}<br>{{ $row['birth_date'] }}</td>
                <td>{{ $row['grade'] }}</td>
                <td class="center">{{ $row['grade_tmt'] }}</td>
                <td>{{ $row['position'] }}<br>{{ $row['position_tmt'] }}</td>
                <td class="center">{{ $row['mkg'] }}</td>
                <td class="center">{{ $row['mks'] }}</td>
                <td>{{ $row['school'] }}</td>
                <td class="center">{{ $row['graduated'] }}</td>
                <td>{{ $row['level_major'] }}</td>
                <td class="center">{{ $row['joined'] }}</td>
                <td class="center">{{ $row['retirement'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="13" class="center">Tidak ada data.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td style="width: 70%;"></td>
            <td style="width: 30%;">
                {{ $city }}, {{ $printedAt }}<br>
                Kepala Sekolah<br>
                <br><br><br><br>
                {{ $headName ?: '..............................' }}<br>
                NIP {{ $headNip ?: '..............................' }}
            </td>
        </tr>
    </table>
</body>

</html>
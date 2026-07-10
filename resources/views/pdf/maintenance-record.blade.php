<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: garuda, sans-serif; }
        body { font-size: 8pt; color: #1f2937; line-height: 1.08; }
        table { border-collapse: collapse; }
        .w100 { width: 100%; }
        .center { text-align: center; }
        .right { text-align: right; }
        .vtop { vertical-align: top; }
        .muted { color: #64748b; }

        /* Header band */
        .hband td { background: #1e293b; padding: 4px 14px; vertical-align: middle; }
        .htitle { color: #ffffff; font-size: 15pt; font-weight: bold; }
        .hsub { color: #94a3b8; font-size: 8pt; letter-spacing: .5px; }
        .logochip { background: #ffffff; padding: 5px 7px; }
        .confbadge { background: #f59e0b; color: #ffffff; padding: 2px 9px; font-size: 7.5pt; }

        /* Info strip */
        .info td { background: #f1f5f9; padding: 2px 14px; }
        .info .lbl { color: #64748b; font-size: 7pt; }
        .info .val { font-size: 9pt; font-weight: bold; color: #0f172a; }

        /* Section heading */
        .sect { font-size: 9.5pt; font-weight: bold; color: #1e293b; padding: 1px 0 1px 7px; border-left: 3px solid #4f46e5; margin: 3px 0 1px; }

        /* Data grid */
        .grid { width: 100%; }
        .grid th { background: #334155; color: #ffffff; font-weight: bold; text-align: center; font-size: 7.5pt; border: 0.4px solid #475569; padding: 2px 2px; }
        .grid td { border: 0.4px solid #d7dee8; padding: 0.5px 3px; line-height: 1.15; }
        .grid.loose td { line-height: 1.35; }
        .grid tbody tr.alt td { background: #f8fafc; }
        .grid tbody tr.na td { background: #f8fafc; color: #94a3b8; }
        .chk { color: #16a34a; font-weight: bold; }
        .xno { color: #dc2626; font-weight: bold; }

        /* Analysis cards */
        .cards td { text-align: center; padding: 3px 4px; border: 2px solid #ffffff; }
        .cards .n { font-size: 13pt; font-weight: bold; }
        .cards .l { font-size: 7.5pt; }
        .c-normal { background: #dcfce7; }
        .c-risk { background: #fef3c7; }
        .c-fault { background: #fee2e2; }
        .c-health { background: #e0e7ff; }

        .bg-normal { background: #dcfce7; }
        .bg-risk { background: #fef3c7; }
        .bg-fault { background: #fee2e2; }

        /* Signature cards */
        .sigcard { width: 100%; border: 0.6px solid #cbd5e1; }
        .sigcard td { padding: 2px 10px; }
        .sigtitle { background: #f1f5f9; font-weight: bold; color: #334155; text-align: center; border-bottom: 0.5px solid #e2e8f0; padding: 1px; }
        .sigblank { height: 18px; text-align: center; vertical-align: middle; }
        .signame { text-align: center; font-weight: bold; color: #0f172a; }
        .sigmeta { color: #64748b; font-size: 7.5pt; }
    </style>
</head>
<body>
    {{-- Header band --}}
    <table class="w100">
        <tr class="hband">
            <td width="13%"><div class="logochip"><img src="{{ $logo }}" style="width:60px;"></div></td>
            <td class="center">
                <div class="htitle">รายงานการบำรุงรักษาระบบ Server</div>
                <div class="hsub">SERVER MAINTENANCE RECORD</div>
            </td>
            <td width="13%"></td>
        </tr>
    </table>

    {{-- Info strip --}}
    <table class="w100 info" style="margin-top:6px;">
        <tr>
            <td width="30%"><div class="lbl">ชื่อเครื่อง SERVER</div><div class="val">{{ $server }}@if($serverType) <span class="muted" style="font-weight:normal; font-size:8pt;">· {{ $serverType }}</span>@endif</div></td>
            <td width="14%"><div class="lbl">ประจำปี (พ.ศ.)</div><div class="val">{{ $year }}</div></td>
            <td width="24%"><div class="lbl">ผู้รับผิดชอบ</div><div class="val">{{ $responsible }}</div></td>
            <td><div class="lbl">สถานะ / รอบที่พิมพ์</div><div class="val">{{ $statusLabel }} <span class="muted" style="font-weight:normal;">· {{ $roundLabel }}</span>@if($createdRevision) <span class="muted" style="font-weight:normal;">· Rev.{{ $createdRevision }}</span>@endif</div></td>
        </tr>
    </table>

    {{-- Checklist grid --}}
    <div class="sect">รายการตรวจเช็ครายเดือน</div>
    <table class="grid loose">
        <thead>
            <tr>
                <th width="3%">#</th>
                <th width="48%" style="text-align:left;">รายการตรวจเช็ค</th>
                @foreach($months as $m)<th>{{ $m }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $item)
                @php $isNa = in_array($item['id'], $na); @endphp
                <tr class="{{ $idx % 2 ? 'alt' : '' }}{{ $isNa ? ' na' : '' }}">
                    <td class="center muted">{{ $idx + 1 }}</td>
                    <td>{{ $item['name'] }}@if($item['freq'])<span class="muted" style="font-size:7pt;"> ({{ $item['freq'] }})</span>@endif</td>
                    @if($isNa)
                        <td class="center" colspan="12" style="font-style:italic; color:#94a3b8;">ไม่เกี่ยวข้อง (N/A)</td>
                    @endif
                    @unless($isNa)
                        @foreach(range(1,12) as $mo)
                            @php $v = $grid[$item['id']][$mo] ?? ''; @endphp
                            <td class="center @if($v === '/') chk @elseif($v === 'X') xno @endif">{{ $v }}</td>
                        @endforeach
                    @endunless
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="muted" style="font-size:7pt; margin-top:2px;">
        <span class="chk">/</span> ตรวจเช็คแล้ว &nbsp;&nbsp; <span class="xno">X</span> ผิดปกติ/ขัดข้อง &nbsp;&nbsp; (ว่าง) ไม่ได้ตรวจเช็ค &nbsp;&nbsp; N/A = ไม่เกี่ยวข้องกับ Server นี้
    </div>

    {{-- Readings + standards --}}
    <table class="w100" style="margin-top:6px;">
        <tr>
            <td width="65%" class="vtop" style="padding-right:9px;">
                <div class="sect">ค่าที่วัดได้รายเดือน & การปรับปรุงรักษา</div>
                <table class="grid">
                    <thead>
                        <tr>
                            <th width="8%">เดือน</th>
                            <th width="14%">วันที่</th>
                            <th width="11%">CPU %</th>
                            <th width="13%">Mem Used %</th>
                            <th width="12%">Disk Used %</th>
                            <th style="text-align:left;">หมายเหตุ / การปรับปรุงรักษา</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(range(1,12) as $mo)
                            @php $r = $readings[$mo]; @endphp
                            <tr>
                                <td class="center">{{ $months[$mo-1] }}</td>
                                <td class="center muted">{{ $r['date'] }}</td>
                                <td class="center @if($r['cpu_s']) bg-{{ $r['cpu_s'] }} @endif">{{ $r['cpu'] }}</td>
                                <td class="center @if($r['mem_s']) bg-{{ $r['mem_s'] }} @endif">{{ $r['mem'] }}</td>
                                <td class="center @if($r['disk_s']) bg-{{ $r['disk_s'] }} @endif">{{ $r['disk'] }}</td>
                                <td style="font-size:7pt;">{!! nl2br(e($r['note'])) !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td width="35%" class="vtop">
                <div class="sect">เกณฑ์มาตรฐานที่กำหนด</div>
                <table class="grid">
                    <thead>
                        <tr>
                            <th style="text-align:left;">รายการ</th>
                            <th>ปกติ</th>
                            <th>เสี่ยง</th>
                            <th>ขัดข้อง</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($standards as $s)
                            <tr>
                                <td>{{ $s['label'] }}</td>
                                <td class="center bg-normal">{{ $s['normal'] }}</td>
                                <td class="center bg-risk">{{ $s['risk'] }}</td>
                                <td class="center bg-fault">{{ $s['fault'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="muted" style="font-size:6.8pt; margin-top:4px;">
                    ระบบวิเคราะห์ค่าที่วัดได้เทียบเกณฑ์นี้ และไฮไลต์สีในตารางด้านซ้ายอัตโนมัติ
                </div>

                <div class="sect" style="margin-top:7px;">สรุปผลการวิเคราะห์ (CPU / Memory / Disk)</div>
                <table class="w100 cards">
                    <tr>
                        <td width="50%" class="c-normal"><div class="n" style="color:#15803d;">{{ $summary['normal'] }}</div><div class="l" style="color:#166534;">ปกติ</div></td>
                        <td width="50%" class="c-risk"><div class="n" style="color:#b45309;">{{ $summary['risk'] }}</div><div class="l" style="color:#92400e;">เสี่ยง</div></td>
                    </tr>
                    <tr>
                        <td width="50%" class="c-fault"><div class="n" style="color:#b91c1c;">{{ $summary['fault'] }}</div><div class="l" style="color:#991b1b;">ขัดข้อง</div></td>
                        <td width="50%" class="c-health"><div class="n" style="color:#4338ca;">{{ $health !== null ? $health.'%' : '-' }}</div><div class="l" style="color:#3730a3;">คะแนนสุขภาพระบบ</div></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Signatures --}}
    <table class="w100" style="margin-top:2px;">
        <tr>
            <td width="50%" class="vtop" style="padding-right:10px;">
                <table class="sigcard">
                    <tr><td class="sigtitle" colspan="2">ผู้ตรวจเช็ค / ผู้จัดทำ</td></tr>
                    <tr><td class="sigblank" colspan="2">@if($preparedSig)<img src="{{ $preparedSig }}" style="height:18px;">@endif</td></tr>
                    <tr><td class="signame" colspan="2">( {{ $preparedName ?: '.....................................' }} )</td></tr>
                    <tr><td class="sigmeta" width="55%">ตำแหน่ง: {{ $preparedPosition ?: '-' }}</td><td class="sigmeta right">วันที่: {{ $preparedDate ?: '......./......./.......' }}</td></tr>
                </table>
            </td>
            <td width="50%" class="vtop" style="padding-left:10px;">
                <table class="sigcard">
                    <tr><td class="sigtitle" colspan="2">ผู้ตรวจสอบ / ผู้อนุมัติ</td></tr>
                    <tr><td class="sigblank" colspan="2">@if($approvedSig)<img src="{{ $approvedSig }}" style="height:18px;">@endif</td></tr>
                    <tr><td class="signame" colspan="2">( {{ $approvedName ?: '.....................................' }} )</td></tr>
                    <tr><td class="sigmeta" width="55%">ตำแหน่ง: {{ $approvedPosition ?: '-' }}</td><td class="sigmeta right">วันที่: {{ $approvedDate ?: '......./......./.......' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

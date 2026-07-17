<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: sarabun, sans-serif; }
        body { font-size: 8pt; color: #1f2937; line-height: 1.08; }
        table { border-collapse: collapse; }
        .w100 { width: 100%; }
        .center { text-align: center; }
        .right { text-align: right; }
        .vtop { vertical-align: top; }
        .muted { color: #64748b; }

        .hband td { background: #1e293b; padding: 4px 14px; vertical-align: middle; }
        .htitle { color: #ffffff; font-size: 15pt; font-weight: bold; }
        .hsub { color: #94a3b8; font-size: 8pt; letter-spacing: .5px; }
        .logochip { background: #ffffff; padding: 5px 7px; }

        .info td { background: #f1f5f9; padding: 2px 14px; }
        .info .lbl { color: #64748b; font-size: 7pt; }
        .info .val { font-size: 9pt; font-weight: bold; color: #0f172a; }

        .sect { font-size: 9.5pt; font-weight: bold; color: #1e293b; padding: 1px 0 1px 7px; border-left: 3px solid #4f46e5; margin: 5px 0 2px; }

        .legendbox { border: 0.5px solid #cbd5e1; background: #f8fafc; padding: 3px 7px; font-size: 7.3pt; line-height: 1.45; }
        .legendbox b { color: #4338ca; }

        /* Document control box (header) */
        .doctable { width: 100%; border-collapse: collapse; }
        .doctable td { border: 0.5px solid #64748b; font-size: 7pt; padding: 1.5px 5px; }
        .doctable .dl { background: #e2e8f0; color: #475569; }
        .doctable .dv { background: #ffffff; font-weight: bold; color: #0f172a; }

        /* Clean checklist legend grid */
        .legendgrid { width: 100%; border-collapse: collapse; }
        .legendgrid td { border: 0.4px solid #cbd5e1; padding: 2.5px 6px; font-size: 7.6pt; vertical-align: middle; }
        .legendgrid .num { background: #334155; color: #ffffff; font-weight: bold; text-align: center; width: 18px; }
        .legendgrid .nm { color: #1e293b; }

        /* Denser, page-filling matrix rows */
        .matrix td { padding: 3px 3px; line-height: 1.5; }
        .matrix th { padding: 3px 2px; }

        /* Compact standards table in header */
        .stdmini { width: 100%; border-collapse: collapse; }
        .stdmini th { background: #334155; color: #ffffff; font-weight: bold; text-align: center; font-size: 6.8pt; border: 0.5px solid #475569; padding: 1.5px 4px; }
        .stdmini td { border: 0.5px solid #94a3b8; font-size: 6.8pt; padding: 1.5px 4px; }
        .stdmini .lbl { text-align: left; font-weight: bold; color: #0f172a; }

        .grid { width: 100%; }
        .grid th { background: #334155; color: #ffffff; font-weight: bold; text-align: center; font-size: 7.3pt; border: 0.4px solid #475569; padding: 2px 2px; }
        .grid td { border: 0.4px solid #d7dee8; padding: 1px 3px; line-height: 1.15; }
        .grid tbody tr.alt td { background: #f8fafc; }
        .grid tbody tr.na td { color: #64748b; }
        .chk { color: #16a34a; font-weight: bold; }
        .xno { color: #dc2626; font-weight: bold; }

        .bg-normal { background: #dcfce7; }
        .bg-risk { background: #fef3c7; }
        .bg-fault { background: #fee2e2; }

        .cards td { text-align: center; padding: 3px 4px; border: 2px solid #ffffff; }
        .cards .n { font-size: 13pt; font-weight: bold; }
        .cards .l { font-size: 7.5pt; }
        .c-done { background: #dcfce7; }
        .c-issue { background: #fee2e2; }
        .c-na { background: #fef3c7; }
        .c-total { background: #e0e7ff; }

        .sigcard { width: 100%; border: 0.6px solid #cbd5e1; }
        .sigcard td { padding: 2px 10px; }
        .sigtitle { background: #f1f5f9; font-weight: bold; color: #334155; text-align: center; border-bottom: 0.5px solid #e2e8f0; padding: 1px; }
        .sigblank { height: 18px; text-align: center; vertical-align: middle; }
        .signame { text-align: center; font-weight: bold; color: #0f172a; }
        .sigmeta { color: #64748b; font-size: 7.5pt; }
    </style>
</head>
<body>
    {{-- Header: title band (left) + standards criteria (right, where doc box was) --}}
    <table class="w100">
        <tr class="vtop">
            <td width="57%" style="background:#1e293b; vertical-align:middle;">
                <table class="w100"><tr>
                    <td width="26%" style="padding:4px 8px;"><div class="logochip"><img src="{{ $logo }}" style="width:54px;"></div></td>
                    <td class="center" style="padding:4px 6px;">
                        <div class="htitle">รายงานการบำรุงรักษาเครื่อง Client</div>
                        <div class="hsub">CLIENT MAINTENANCE RECORD</div>
                    </td>
                </tr></table>
            </td>
            <td width="43%" style="padding-left:8px; vertical-align:middle;">
                @if(count($standards))
                    <table class="grid">
                        <thead>
                            <tr>
                                <th style="text-align:left;">เกณฑ์มาตรฐานที่ใช้ประเมิน</th>
                                <th width="22%">ปกติ</th>
                                <th width="22%">เสี่ยง</th>
                                <th width="22%">ขัดข้อง</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($standards as $s)
                                <tr>
                                    <td>{{ $s['short'] }}</td>
                                    <td class="center bg-normal">{{ $s['normal'] ?: '-' }}</td>
                                    <td class="center bg-risk">{{ $s['risk'] ?: '-' }}</td>
                                    <td class="center bg-fault">{{ $s['fault'] ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    {{-- Info strip --}}
    <table class="w100 info" style="margin-top:6px;">
        <tr>
            <td width="22%"><div class="lbl">ประจำเดือน / ปี (พ.ศ.)</div><div class="val">{{ $monthLabel }} {{ $year }}</div></td>
            <td width="26%"><div class="lbl">ผู้รับผิดชอบ</div><div class="val">{{ $responsible }}</div></td>
            <td width="18%"><div class="lbl">สถานะ</div><div class="val">{{ $statusLabel }}</div></td>
            <td><div class="lbl">จำนวนเครื่อง</div><div class="val">{{ $machineCount }} เครื่อง <span class="muted" style="font-weight:normal; font-size:8pt;">· ผ่าน {{ $counts['done'] }} · มีปัญหา {{ $counts['issue'] }} · ยังไม่ครบ {{ $counts['na'] }}</span></div></td>
        </tr>
    </table>

    {{-- Checklist legend --}}
    @if(count($tasksDef))
        <div class="sect">รายการตรวจเช็ค (Checklist) — เลขกำกับตรงกับหัวคอลัมน์ในตาราง</div>
        <table class="legendgrid">
            @foreach(array_chunk($tasksDef, 2, true) as $pair)
                <tr>
                    @foreach($pair as $i => $t)
                        <td class="num">{{ $i + 1 }}</td><td class="nm" width="49%">{{ $t['name'] }}</td>
                    @endforeach
                    @if(count($pair) === 1)<td class="num"></td><td></td>@endif
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Machine × task matrix --}}
    <div class="sect">ผลการตรวจเช็ครายเครื่อง</div>
    <table class="grid matrix">
        <thead>
            <tr>
                <th width="3%">#</th>
                <th width="16%" style="text-align:left;">เครื่อง</th>
                <th width="7%">ชั้น</th>
                @foreach($standards as $s)<th width="7%">{{ $s['short'] }}</th>@endforeach
                @foreach($tasksDef as $i => $t)<th width="3%">{{ $i + 1 }}</th>@endforeach
                <th width="5%">ปัญหา</th>
                <th style="text-align:left;">หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $idx => $r)
                <tr class="{{ $idx % 2 ? 'alt' : '' }}{{ $r['na'] ? ' na' : '' }}">
                    <td class="center muted">{{ $idx + 1 }}</td>
                    <td>{{ $r['name'] }}</td>
                    <td class="center muted">{{ $r['floor'] ?: '-' }}</td>
                    @foreach($r['metrics'] as $m)
                        <td class="center @if($m['status']) bg-{{ $m['status'] }} @endif">{{ $m['val'] !== '' ? $m['val'] : '–' }}</td>
                    @endforeach
                    @foreach($r['tasks'] as $done)
                        <td class="center @if($done) chk @endif">{{ $done ? '/' : '' }}</td>
                    @endforeach
                    <td class="center @if($r['issue']) xno @endif">{{ $r['issue'] ? 'X' : '' }}</td>
                    <td style="font-size:7pt;">{!! nl2br(e($r['note'])) !!}</td>
                </tr>
            @endforeach
            @if(!count($rows))
                <tr><td class="center muted" colspan="{{ 5 + count($standards) + count($tasksDef) }}" style="padding:8px;">ไม่มีข้อมูลเครื่อง</td></tr>
            @endif
        </tbody>
    </table>
    <div class="muted" style="font-size:7pt; margin-top:2px;">
        <span class="chk">/</span> ทำแล้ว &nbsp;&nbsp; (ว่าง) ยังไม่ได้ทำ &nbsp;&nbsp; <span class="xno">X</span> พบปัญหา &nbsp;&nbsp; ค่าตรวจวัด: <span class="bg-normal">&nbsp;ปกติ&nbsp;</span> <span class="bg-risk">&nbsp;เสี่ยง&nbsp;</span> <span class="bg-fault">&nbsp;ขัดข้อง&nbsp;</span>
    </div>

    {{-- Summary + note --}}
    <div class="sect" style="margin-top:6px;">สรุปผลรายเครื่อง</div>
    <table class="w100 cards">
        <tr>
            <td width="25%" class="c-done"><div class="n" style="color:#15803d;">{{ $counts['done'] }}</div><div class="l" style="color:#166534;">ผ่าน (ครบทุกงาน)</div></td>
            <td width="25%" class="c-issue"><div class="n" style="color:#b91c1c;">{{ $counts['issue'] }}</div><div class="l" style="color:#991b1b;">มีปัญหา</div></td>
            <td width="25%" class="c-na"><div class="n" style="color:#b45309;">{{ $counts['na'] }}</div><div class="l" style="color:#92400e;">ยังไม่ครบ</div></td>
            <td width="25%" class="c-total"><div class="n" style="color:#4338ca;">{{ $machineCount }}</div><div class="l" style="color:#3730a3;">รวมทั้งหมด</div></td>
        </tr>
    </table>
    @if($note)
        <div class="sect" style="margin-top:6px;">หมายเหตุรวม</div>
        <div style="font-size:8pt;">{!! nl2br(e($note)) !!}</div>
    @endif

    {{-- Signatures --}}
    <table class="w100" style="margin-top:4px;">
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

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 15mm 18mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1e293b; line-height: 1.6; padding: 12mm 16mm; }

    .cover { text-align: center; padding: 30mm 0 20mm; border-bottom: 3px solid #0f2744; margin-bottom: 20px; }
    .cover .sistema { font-size: 9px; letter-spacing: 2px; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
    .cover h1 { font-size: 26px; color: #0f2744; font-weight: 700; margin-bottom: 6px; }
    .cover .subtitulo { font-size: 13px; color: #475569; margin-bottom: 20px; }
    .cover .meta { font-size: 9px; color: #94a3b8; }

    h2 { font-size: 14px; font-weight: 700; color: #0f2744; border-left: 4px solid #0f7ef4; padding-left: 8px; margin: 18px 0 8px; page-break-after: avoid; }
    h3 { font-size: 11px; font-weight: 700; color: #1e3a5f; margin: 12px 0 5px; page-break-after: avoid; }
    h4 { font-size: 10px; font-weight: 700; color: #334155; margin: 8px 0 4px; }

    p { margin-bottom: 7px; }

    table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; font-size: 9.5px; }
    thead tr { background: #0f2744; color: #fff; }
    thead th { padding: 5px 8px; text-align: left; font-weight: 600; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; }

    pre, code { font-family: DejaVu Sans Mono, monospace; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 3px; }
    pre { padding: 8px 10px; margin: 6px 0 10px; font-size: 8.5px; white-space: pre-wrap; word-break: break-all; }
    code { padding: 1px 4px; font-size: 8.5px; }

    .note { background: #eff6ff; border-left: 3px solid #3b82f6; padding: 7px 10px; margin: 8px 0; font-size: 9.5px; border-radius: 0 3px 3px 0; }
    .warn { background: #fefce8; border-left: 3px solid #eab308; padding: 7px 10px; margin: 8px 0; font-size: 9.5px; border-radius: 0 3px 3px 0; }

    ul, ol { padding-left: 16px; margin-bottom: 8px; }
    li { margin-bottom: 3px; font-size: 10px; }

    .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 8px; font-weight: 700; }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    .badge-green { background: #dcfce7; color: #15803d; }
    .badge-orange { background: #ffedd5; color: #c2410c; }
    .badge-red { background: #fee2e2; color: #b91c1c; }
    .badge-gray { background: #f1f5f9; color: #475569; }

    hr { border: none; border-top: 1px solid #e2e8f0; margin: 14px 0; }

    .footer { position: fixed; bottom: 8mm; left: 16mm; right: 16mm; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px; text-align: right; }
</style>
</head>
<body>
    @yield('contenido')
    <div class="footer">SIP — Sistema Integral de Pedidos &nbsp;|&nbsp; {{ now()->format('d/m/Y') }}</div>
</body>
</html>

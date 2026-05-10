<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            margin: 0;
            padding: 24px 0;
            background: #f4f7fb;
            color: #1f2937;
            font-family: Arial, sans-serif;
        }

        .wrapper {
            max-width: 640px;
            margin: 0 auto;
        }

        .card {
            background: #ffffff;
            border: 1px solid #d9e4f0;
            border-radius: 16px;
            overflow: hidden;
        }

        .header {
            background: #0f7abf;
            color: #ffffff;
            padding: 24px;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .header h1 {
            margin: 14px 0 0;
            font-size: 24px;
            line-height: 1.25;
        }

        .content {
            padding: 24px;
        }

        .content p {
            margin: 0 0 14px;
            line-height: 1.6;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .summary td {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .summary tr:last-child td {
            border-bottom: none;
        }

        .summary td:first-child {
            width: 38%;
            font-weight: 700;
            color: #334155;
            background: #f8fbff;
        }

        .cta {
            margin-top: 22px;
        }

        .cta a {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 10px;
            background: #0f7abf;
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
        }

        .footer {
            padding: 16px 24px;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            background: #f8fbff;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <span class="badge">{{ $badge }}</span>
                <h1>{{ $headline }}</h1>
            </div>

            <div class="content">
                <p>{{ $lead }}</p>
                <p>{{ $note }}</p>

                <table class="summary" role="presentation">
                    <tbody>
                    @foreach ($summary_rows as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="cta">
                    <a href="{{ $cta_url }}">{{ $cta_label }}</a>
                </div>
            </div>

            <div class="footer">
                Angelow Inventario · Este mensaje fue generado automáticamente para seguimiento de reposición.
            </div>
        </div>
    </div>
</body>
</html>
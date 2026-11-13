<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seu código de confirmação</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#171717;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:420px;background:#ffffff;border-radius:12px;padding:32px 24px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 4px;font-size:13px;color:#737373;">{{ $nomeDoEstabelecimento }}</p>
                            <h1 style="margin:0 0 16px;font-size:20px;font-weight:600;">Olá, {{ $nome }}!</h1>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.5;">
                                Use o código abaixo para confirmar seu e-mail e terminar de criar sua conta.
                            </p>
                            <p style="margin:0 0 24px;text-align:center;font-size:32px;font-weight:700;letter-spacing:8px;font-family:'SFMono-Regular',Menlo,Consolas,monospace;">
                                {{ $codigo }}
                            </p>
                            <p style="margin:0 0 8px;font-size:13px;color:#737373;line-height:1.5;">
                                O código vale por {{ $validadeEmMinutos }} minutos.
                            </p>
                            <p style="margin:0;font-size:13px;color:#737373;line-height:1.5;">
                                Se não foi você que pediu, ignore este e-mail: nenhuma conta será criada.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

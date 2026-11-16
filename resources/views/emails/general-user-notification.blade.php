<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Clube Lupa</title>
</head>

<body style="Margin:0;padding:0;background-color:#f2f2f0;">
    <center style="width:100%;background-color:#f2f2f0;">
        <!-- Largura máxima do e‑mail -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
            style="max-width:1200px;margin:0 auto;">
            <tr>
                <td style="padding:0 10px 40px 10px;">
                    <!-- Duas colunas: imagem + texto -->
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                        <tr>
                            <!-- COLUNA IMAGEM COM FUNDO LARANJA -->
                            <td width="600" valign="top" style="background-color:#dfa079;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    border="0">
                                    <tr>
                                        <td align="center" style="padding:60px 0;">
                                            <img src={{ asset('images/logo.png') }} alt="Logo"
                                                style="display:block; border:0; outline:none; text-decoration:none; font-size:16px;" />
                                        </td>
                                        {{-- <td align="center" style="padding:60px 0;">
                                            <img src="https://clube-lupa.s3.sa-east-1.amazonaws.com/hero.png"
                                                width="400" alt="Imagem do App Lupa em um celular"
                                                style="display:block;border:0;outline:none;text-decoration:none;font-size:16px;" />
                                        </td> --}}
                                    </tr>
                                </table>
                            </td>

                            <!-- COLUNA DE TEXTO -->
                            <td width="600" valign="top" style="padding:40px 40px 40px 40px;">
                                <!-- Saudação -->
                                <p
                                    style="font-family:Helvetica,Arial,sans-serif;color:#969f5e;font-size:24px;margin:0 0 40px 0;">
                                    Olá, <strong>{{ $name }}</strong>.
                                </p>

                                <!-- Título principal -->
                                <h1
                                    style="font-family:Georgia,'Times New Roman',Times,serif;color:#4e5153;font-size:24px;line-height:1;margin:0 0 0 0;">
                                    {{ $title }}
                                </h1>
                                <br>
                                <!-- Sub‑título itálico -->
                                <h2
                                    style="font-family:Georgia,'Times New Roman',Times,serif;font-style:italic;color:#dfa079;font-size:24px;line-height:1;margin:0 0 20px 0;">
                                    {{ $body }}
                                </h2>

                                <!-- Linha divisória -->
                                <hr style="border:0;height:1px;background-color:#c9c9ca;margin:0 0 20px 0;" />

                                <!-- Chamada “Aproveite!” -->
                                <p
                                    style="font-family:Georgia,'Times New Roman',Times,serif;color:#4e5153;font-size:24px;line-height:1;margin:0 0 30px 0;">
                                    Aproveite!
                                </p>


    </center>
</body>

</html>

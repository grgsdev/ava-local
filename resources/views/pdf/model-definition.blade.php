{{-- Em resources/views/pdf/model-definition.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $model['name'] }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .container {
            padding: 20px;
        }

        h1,
        h2 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 4px;
            background-color: #eee;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Documentação do Modelo: {{ $model['name'] }}</h1>
        <p><strong>Versão:</strong> {{ $model['version'] }}</p>

        <h2>Atributos</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Opções</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($model['attributes'] as $attr)
                    <tr>
                        <td>{{ $attr['name'] }}</td>
                        <td><span class="badge">{{ $attr['type'] }}</span></td>
                        <td>
                            @if ($attr['required'])
                                Requerido,
                            @endif
                            @if ($attr['nullable'])
                                Nulável,
                            @endif
                            @if ($attr['unique'])
                                Único
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (!empty($model['relations']))
            <h2>Relações</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Modelo Relacionado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($model['relations'] as $rel)
                        <tr>
                            <td>{{ $rel['name'] }}</td>
                            <td><span class="badge">{{ $rel['type'] }}</span></td>
                            <td>{{ $rel['related'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>

</html>

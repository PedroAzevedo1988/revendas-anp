<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revendas ANP</title>

    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">

    <style>
        :root {
            --azul-principal: #0B2D5C;
            --fundo-principal: #EDEDCE;
            --branco: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--fundo-principal);
        }

        .barra-superior,
        .barra-inferior {
            width: 100%;
            height: 50px;
            background-color: var(--azul-principal);
        }

        .barra-inferior {
            margin-top: 30px;
        }

        .conteudo {
            padding: 30px;
            min-height: calc(100vh - 100px);
        }

        h1 {
            color: var(--azul-principal);
            margin-bottom: 25px;
        }

        .filtros {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .filtros input,
        .filtros select {
            width: 100%;
            height: 40px;
            padding: 8px;
            border: 1px solid #CCC;
            border-radius: 4px;
            background-color: white;
        }

       .campo-data {
    position: relative;
    width: 100%;
}
        .campo-data {
            position: relative;
            width: 100%;
        }

        .campo-data input {
            width: 100%;
            height: 40px;
            padding: 8px;
            color: transparent;
        }

        .campo-data input:focus,
        .campo-data input:valid {
            color: black;
        }

        .campo-data span {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #666;
            pointer-events: none;
        }

        .campo-data input:focus + span,
        .campo-data input:valid + span {
            display: none;
        }

                .acoes {
                    margin-bottom: 20px;
                }

                button {
                    background-color: var(--azul-principal);
                    color: white;
                    border: none;
                    border-radius: 4px;
                    padding: 10px 16px;
                    margin-right: 10px;
                    cursor: pointer;
                    font-weight: bold;
                }

                table.dataTable {
                    background-color: white;
                }
    </style>
</head>

<body>

<header class="barra-superior"></header>

<main class="conteudo">

    <h1>Revendas ANP</h1>

    <div class="filtros">
        <input type="text" id="filtroCnpj" placeholder="CNPJ">
        <input type="text" id="filtroCodigoisimp" placeholder="CODIGOISIMP">

        <select id="filtroUf">
            <option value="">Todas as UFs</option>
        </select>

        <select id="filtroMunicipio" disabled>
            <option value="">Selecione uma UF primeiro</option>
        </select>

        <div class="campo-data">
            <input type="date" id="filtroDataVinculacao" required>
            <span>Data Vinculação</span>
        </div>

        <div class="campo-data">
            <input type="date" id="filtroDataPublicacao" required>
         <span>Data Publicação</span>
        </div>
    </div>

    <div class="acoes">
        <button id="btnFiltrar">Filtrar</button>
        <button id="btnLimpar">Limpar filtros</button>
    </div>

    <table id="tabelaRevendas" class="display">
        <thead>
            <tr>
                <th>CNPJ</th>
                <th>CODIGOISIMP</th>
                <th>Razão Social</th>
                <th>UF</th>
                <th>Município</th>
                <th>Autorização</th>
                <th>Data Vinculação</th>
                <th>Data Publicação</th>
            </tr>
        </thead>
    </table>

</main>

<footer class="barra-inferior"></footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>

<script>
$(document).ready(function () {

    function carregarUFs() {
        $.getJSON('filtros.php', { tipo: 'ufs' }, function (ufs) {
            let options = '<option value="">Todas as UFs</option>';

            ufs.forEach(function (uf) {
                options += `<option value="${uf}">${uf}</option>`;
            });

            $('#filtroUf').html(options);
        });
    }

    function carregarMunicipios(uf) {
        $('#filtroMunicipio')
            .prop('disabled', true)
            .html('<option value="">Carregando...</option>');

        $.getJSON('filtros.php', { tipo: 'municipios', uf: uf }, function (municipios) {
            let options = '<option value="">Todos os Municípios</option>';

            municipios.forEach(function (municipio) {
                options += `<option value="${municipio}">${municipio}</option>`;
            });

            $('#filtroMunicipio')
                .html(options)
                .prop('disabled', false);
        });
    }

    carregarUFs();

    const tabela = $('#tabelaRevendas').DataTable({
        processing: true,
        serverSide: true,
        searching: false,

        ajax: {
            url: 'revendas_anp_selecionar.php',
            type: 'GET',
            data: function (d) {
                d.cnpj = $('#filtroCnpj').val();
                d.codigoisimp = $('#filtroCodigoisimp').val();
                d.uf = $('#filtroUf').val();
                d.municipio = $('#filtroMunicipio').val();
                d.datavinculacao = $('#filtroDataVinculacao').val();
                d.datapublicacao = $('#filtroDataPublicacao').val();
            }
        },
        columns: [
            { data: 'CNPJ' },
            { data: 'CODIGOISIMP' },
            { data: 'RAZAOSOCIAL' },
            { data: 'UF' },
            { data: 'MUNICIPIO' },
            { data: 'AUTORIZACAO' },
            { data: 'DATAVINCULACAO' },
            { data: 'DATAPUBLICACAO' }
        ],
        pageLength: 20,
        lengthMenu: [10, 25, 50, 100],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json'
        }
    });

    $('#filtroUf').on('change', function () {
        const uf = $(this).val();

        $('#filtroMunicipio')
            .html('<option value="">Selecione uma UF primeiro</option>')
            .prop('disabled', true);

        if (uf) {
            carregarMunicipios(uf);
        }

        tabela.ajax.reload();
    });

    $('#filtroMunicipio').on('change', function () {
        tabela.ajax.reload();
    });

    $('#btnFiltrar').on('click', function () {
        tabela.ajax.reload();
    });

    $('#btnLimpar').on('click', function () {
        $('.filtros input').val('');

        $('#filtroUf').val('');

        $('#filtroMunicipio')
            .html('<option value="">Selecione uma UF primeiro</option>')
            .prop('disabled', true);

        tabela.ajax.reload();
    });

    $('.filtros input').on('keypress', function (e) {
        if (e.which === 13) {
            tabela.ajax.reload();
        }
    });

});
</script>

</body>
</html>
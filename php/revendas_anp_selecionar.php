<?php
header('Content-Type: application/json; charset=utf-8');

$host = getenv('DB_HOST') ?: 'mssql';
$dbname = getenv('MSSQL_DATABASE');
$user = 'sa';
$password = getenv('MSSQL_SA_PASSWORD');

try {
    $pdo = new PDO(
        "sqlsrv:Server=$host,1433;Database=$dbname;TrustServerCertificate=true",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $columns = [
        'CNPJ',
        'CODIGOISIMP',
        'RAZAOSOCIAL',
        'UF',
        'MUNICIPIO',
        'AUTORIZACAO',
        'DATAVINCULACAO',
        'DATAPUBLICACAO'
    ];

    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);

    $where = [];
    $params = [];

    $filters = [
        'cnpj' => 'CNPJ',
        'codigoisimp' => 'CODIGOISIMP',
        'uf' => 'UF',
        'municipio' => 'MUNICIPIO',
        'datavinculacao' => 'DATAVINCULACAO',
        'datapublicacao' => 'DATAPUBLICACAO'
    ];

    foreach ($filters as $input => $column) {
        if (!empty($_GET[$input])) {
            $where[] = "$column LIKE :$input";
            $params[":$input"] = '%' . $_GET[$input] . '%';
        }
    }

    $whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $totalSql = "SELECT COUNT(*) FROM tb_revendas_anp";
    $totalRecords = $pdo->query($totalSql)->fetchColumn();

    $filteredSql = "SELECT COUNT(*) FROM tb_revendas_anp $whereSql";
    $stmt = $pdo->prepare($filteredSql);
    $stmt->execute($params);
    $filteredRecords = $stmt->fetchColumn();

    $orderColumnIndex = intval($_GET['order'][0]['column'] ?? 2);
    $orderDir = $_GET['order'][0]['dir'] ?? 'asc';

    $orderColumn = $columns[$orderColumnIndex] ?? 'RAZAOSOCIAL';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

    $sql = "
        SELECT
            CNPJ,
            CODIGOISIMP,
            RAZAOSOCIAL,
            UF,
            MUNICIPIO,
            AUTORIZACAO,
            DATAVINCULACAO,
            DATAPUBLICACAO
        FROM tb_revendas_anp
        $whereSql
        ORDER BY $orderColumn $orderDir
        OFFSET :start ROWS FETCH NEXT :length ROWS ONLY
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);

    $stmt->execute();
    $data = $stmt->fetchAll();

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => intval($totalRecords),
        'recordsFiltered' => intval($filteredRecords),
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao consultar o banco de dados',
        'details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
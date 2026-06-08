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

    $tipo = $_GET['tipo'] ?? '';

    if ($tipo === 'ufs') {
        $stmt = $pdo->query("
            SELECT DISTINCT UF
            FROM tb_revendas_anp
            WHERE UF IS NOT NULL
              AND UF <> ''
            ORDER BY UF
        ");

        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($tipo === 'municipios') {
        $uf = $_GET['uf'] ?? '';

        $stmt = $pdo->prepare("
            SELECT DISTINCT MUNICIPIO
            FROM tb_revendas_anp
            WHERE UF = :uf
              AND MUNICIPIO IS NOT NULL
              AND MUNICIPIO <> ''
            ORDER BY MUNICIPIO
        ");

        $stmt->execute([
            ':uf' => $uf
        ]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'erro' => 'Erro ao buscar filtros',
        'detalhes' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
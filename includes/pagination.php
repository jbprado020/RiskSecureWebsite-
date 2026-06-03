<?php

declare(strict_types=1);

function paginatedQuery(
    PDO $pdo,
    string $countSql,
    string $dataSql,
    array $params = [],
    int $perPage = 25,
    string $pageVar = 'page'
): array {
    $page = max(1, (int) ($_GET[$pageVar] ?? 1));

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);

    $offset = ($page - 1) * $perPage;
    $dataStmt = $pdo->prepare($dataSql . ' LIMIT :p_limit OFFSET :p_offset');
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $dataStmt->bindValue($key, $value, $type);
    }
    $dataStmt->bindValue(':p_limit', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':p_offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $data = $dataStmt->fetchAll();

    $from = $total > 0 ? $offset + 1 : 0;
    $to = min($offset + $perPage, $total);

    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => $totalPages,
        'hasPrev' => $page > 1,
        'hasNext' => $page < $totalPages,
        'prevPage' => $page - 1,
        'nextPage' => $page + 1,
        'from' => $from,
        'to' => $to,
    ];
}

function renderPagination(array $p): string
{
    if ($p['totalPages'] <= 1) {
        return '';
    }

    $params = $_GET;
    $html = '<nav class="pagination" aria-label="Pagination">';
    $html .= '<span class="pagination-info">' . $p['from'] . '–' . $p['to'] . ' of ' . $p['total'] . '</span>';
    $html .= '<div class="pagination-links">';

    $params['page'] = $p['prevPage'];
    $prevQuery = http_build_query($params);
    $html .= $p['hasPrev']
        ? '<a href="?' . e($prevQuery) . '" class="pagination-btn" rel="prev">&laquo; Prev</a>'
        : '<span class="pagination-btn disabled">&laquo; Prev</span>';

    $start = max(1, $p['page'] - 2);
    $end = min($p['totalPages'], $p['page'] + 2);

    if ($start > 1) {
        $params['page'] = 1;
        $html .= '<a href="?' . e(http_build_query($params)) . '" class="pagination-btn">1</a>';
        if ($start > 2) {
            $html .= '<span class="pagination-ellipsis">&hellip;</span>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $params['page'] = $i;
        $q = e(http_build_query($params));
        if ($i === $p['page']) {
            $html .= '<span class="pagination-btn current" aria-current="page">' . $i . '</span>';
        } else {
            $html .= '<a href="?' . $q . '" class="pagination-btn">' . $i . '</a>';
        }
    }

    if ($end < $p['totalPages']) {
        if ($end < $p['totalPages'] - 1) {
            $html .= '<span class="pagination-ellipsis">&hellip;</span>';
        }
        $params['page'] = $p['totalPages'];
        $html .= '<a href="?' . e(http_build_query($params)) . '" class="pagination-btn">' . $p['totalPages'] . '</a>';
    }

    $params['page'] = $p['nextPage'];
    $nextQuery = http_build_query($params);
    $html .= $p['hasNext']
        ? '<a href="?' . e($nextQuery) . '" class="pagination-btn" rel="next">Next &raquo;</a>'
        : '<span class="pagination-btn disabled">Next &raquo;</span>';

    $html .= '</div></nav>';
    return $html;
}

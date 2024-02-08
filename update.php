<?php
session_start();
include "includes/utils.php";
$conn = pg_connect("host=localhost port=5432 " .
    "dbname=" . $_SESSION["dbname"] . " " .
    "user=" . $_SESSION["user"] . " " .
    "password=" . $_SESSION["password"]
);
$data = json_decode(file_get_contents('php://input'), true);
echo file_get_contents('php://input'), '<br>';
file_put_contents(
    'data.log',
    date('[d/m/y H:i:s] ') . file_get_contents('php://input') ."\n",
    FILE_APPEND
);
foreach ($data["toAdd"] as $row) {
    foreach ($row as $key => $value) {
        if ($value == null) unset($row[$key]);
    }
    $query_string = str_replace('        ', '',
        'INSERT INTO public."'.$_GET["table"].'"("'.implode('", "', array_keys($row)).'")
        VALUES ($'.implode(', $', range(1, count($row))).');');
    file_put_contents("queries.log",
        date('[d/m/y H:i:s] ') ."\n"
        . $query_string ."\n"
        . var_export(array_values($row), true) ."\n",
        FILE_APPEND);
    echo $query_string;
    pg_query_params(
        $conn,
        $query_string,
        array_values($row)
    ) or header("HTTP/1.1 500 Internal Server Error");
}
foreach ($data["toDelete"] as $row) {
    foreach ($row as $key => $value) {
        if ($value == null) unset($row[$key]);
    }
    $query_string = 'DELETE FROM public."'.$_GET["table"].'" WHERE ';
    for ($i = 0; $i < count($row); $i++) {
        if ($i > 0) $query_string .= ' AND ';

        $query_string .= '"'.array_keys($row)[$i].'" = $'.($i+1);
    }
    file_put_contents("queries.log",
        date('[d/m/y H:i:s] ') ."\n"
        . $query_string ."\n"
        . var_export(array_values($row), true) ."\n",
        FILE_APPEND);
    echo $query_string;
    pg_query_params(
        $conn,
        $query_string,
        array_values($row)
    ) or header("HTTP/1.1 500 Internal Server Error");
}
foreach ($data["toUpdate"] as [$original, $new]) {
    foreach ($original as $key => $value) {
        if ($value == null) unset($original[$key]);
    }
    foreach ($new as $key => $value) {
        if ($value == null) unset($new[$key]);
    }
    $query_string = 'UPDATE public."'.$_GET["table"]."\"\nSET ";
    for ($i = 0; $i < count($new); $i++) {
        if ($i > 0) $query_string .= ', ';

        $query_string .= '"'.array_keys($new)[$i].'" = $'.($i+1);
    }
    $query_string .= "\nWHERE ";
    for ($i = 0; $i < count($original); $i++) {
        if ($i > 0) $query_string .= ' AND ';

        $query_string .= '"'.array_keys($original)[$i].'" = $'.($i+1+count($new));
    }
    file_put_contents("queries.log",
        date('[d/m/y H:i:s] ') ."\n"
        . $query_string ."\n"
        . var_export(array_merge(array_values($new), array_values($original)), true)."\n",
        FILE_APPEND);
    echo $query_string;
    pg_query_params(
        $conn,
        $query_string,
        array_merge(array_values($new), array_values($original))
    ) or header("HTTP/1.1 500 Internal Server Error");
}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>database-web-ui</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/edit.css">
    <script src="scripts/edit.js" defer></script>
</head>
<body>
<div class="container">
    <?php
    include "includes/utils.php";
    session_start();
    $conn = pg_connect("host=localhost port=5432 " .
        "dbname=" . $_SESSION["dbname"] . " " .
        "user=" . $_SESSION["user"] . " " .
        "password=" . $_SESSION["password"]
    );
    $select_foreign = !isset($_GET["selectForeign"]) || $_GET["selectForeign"] == "true";
    $show_foreign = !isset($_GET["showForeign"]) || $_GET["showForeign"] == "true";
    ?>
    <div class="displaySettings">
        <?php echoTableOptions($conn);?>
        <label>
            <input id="selectForeign" type="checkbox" <?=$select_foreign?"checked":""?> onchange="update();">
            Select foreign keys
        </label>
        <label>
            <input id="showForeign" type="checkbox" <?=$show_foreign?"checked":""?> <?=!$select_foreign?"disabled":""?> onchange="update();">
            Show foreign columns
        </label>
    </div>
    <table>
        <?php
        if (!isset($_GET["table"])) {
            echoTemplate();
            exit();
        }

        $query = pg_query_params(
            $conn,
            "SELECT column_name name, data_type type
            FROM information_schema.columns
            WHERE table_name = $1 
            ORDER BY ordinal_position",
            array($_GET["table"])
        );
        $result = pg_fetch_all($query);
        $columns = array();
        for ($i = 0; $i < count($result); $i++) {
            $columns[$result[$i]["name"]] = $result[$i]["type"];
        }

        $query = pg_query_params(
            $conn,
            "SELECT kcu.column_name column, constraint_type type
            FROM information_schema.table_constraints tc
        	JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema=kcu.table_schema
            WHERE tc.table_schema='public' AND kcu.table_name=$1;",
            array($_GET["table"])
        );
        $constraints = array();
        foreach (pg_fetch_all($query) as $constraint) {
            $constraints[$constraint["column"]] = $constraint["type"];
        }

        echo '<tr>';
        foreach ($columns as $name => $type) {
            printf(
                '<th>
                    <b>%s</b><br>
                    <span>%s</span><br>
                    <i>%s</i>
                </th>',
                $name, (isset($constraints[$name])?ucfirst(strtolower($constraints[$name])):''), $type
            );
        }
        echo '<th><b>Delete</b><br><br><br></th></tr>';

        $foreign_keys = array();
        $foreign_data = array();
        if ($select_foreign) {
            $data = pg_fetch_all(
                pg_query_params(
                    $conn,
                    "SELECT kcu.column_name local, ccu.table_name, ccu.column_name
                    FROM information_schema.table_constraints tc
                        JOIN information_schema.key_column_usage kcu
                            ON tc.constraint_name = kcu.constraint_name
                            AND tc.table_schema = kcu.table_schema
                        JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
                    WHERE tc.constraint_type='FOREIGN KEY' AND tc.table_schema='public'
                        AND kcu.table_name=$1;",
                    array($_GET["table"])
                )
            );
            foreach ($data as $row) {
                $foreign_keys[$row["local"]] = array(
                    "table" => $row["table_name"],
                    "column" => $row["column_name"],
                );
                $foreign_data[$row["local"]] = getTableData($conn, $row["table_name"]);
            }
        }
        ?>
        <script>
            let tableData = <?=json_encode(getTableData($conn, $_GET["table"]));?>;
            let columns = <?=json_encode($columns);?>;
            let constraints = <?=json_encode($constraints);?>;
            let foreignKeys = <?=json_encode($foreign_keys);?>;
            let foreignData = <?=json_encode($foreign_data);?>;
        </script>
    </table>
    <div class="actions">
        <input type="button" value="Cancel" onclick="update();">
        <input type="button" value="Save" onclick="sendData();">
    </div>
</div>
<div id="debug" style="display:none;">Nothing to show</div>
</body>
</html>

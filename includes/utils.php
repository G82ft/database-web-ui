<?php
function echoTableOptions($conn)
{
    $query = pg_query(
        $conn,
        'SELECT table_name
        FROM information_schema.tables
        WHERE "table_schema" = \'public\''
    );
    echo '<select id="table" name="table" onchange="update();">
            <option value="">-- Select table --</option>
            ';
    foreach (pg_fetch_all($query) as $table) {
        echo "<option"
                .(isset($_GET["table"]) && $table["table_name"] == $_GET["table"]?" selected": "")
            .">" . $table["table_name"] . "</option>";
    }
    echo '</select>';
}

function echoTemplate()
{
    echo '<tr>
        <th>
            <b>Name</b><br>
            <span>Constraint</span><br>
            <i>type</i>
        </th>
    </tr>
    <tr>
        <td>
            <input type="text" disabled>
        </td>
    </tr>';
}

function getTableData($conn, $table, $mode=PGSQL_ASSOC) {
    return pg_fetch_all(
        pg_query(
            $conn,
            'SELECT *
            FROM public."' . $table . '"'
        ), $mode
    );
}

function echoForeignSelect($column, $foreign_key, $foreign_data, $show_foreign) {
    echo '<td>
            <select>';
    foreach ($foreign_data as $subrow) {
        echo '<option value="'.$column.'" '.($subrow[$foreign_key["column"]] == $column ? 'selected':'').'>';
        if ($show_foreign) {
            echo implode(' | ', $subrow);
        }
        else {
            echo implode(' | ', array_diff_assoc($subrow, array($foreign_key["column"] => $subrow[$foreign_key["column"]])));
        }
        echo '</option>';
    }
    echo '    </select>
    </td>';
}


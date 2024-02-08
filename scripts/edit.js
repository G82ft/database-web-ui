for (let row of tableData) {
    for (let [key, value] of Object.entries(row)) {
        row[key] = parseValue(value, columns[key]);
    }
}
for (let data of Object.values(foreignData)) {
    for (let row of data) {
        for (let [key, value] of Object.entries(row)) {
            let type = columns[key];
            if (type === undefined) {
                continue;
            }
            row[key] = parseValue(value, columns[key]);
        }
    }
}

let selectedTable = document.getElementById("table");
let selectForeign = document.getElementById("selectForeign");
let showForeign = document.getElementById("showForeign");

let debug = document.getElementById('debug');
function update() {
    location.search = selectedTable.value ?
        `?table=${selectedTable.value}&selectForeign=${selectForeign.checked}&showForeign=${showForeign.checked}` : '';
}

let changed = false;

let table = document.getElementsByTagName("table")[0];

let toDelete = [];

function fillTable() {
    for (let i = 0; i < tableData.length; i++) {
        let row = tableData[i];
        insertRow(columns, constraints, foreignKeys, foreignData, showForeign.checked, selectForeign.checked, row);
    }
}

function addEmptyRow() {
    insertRow(columns, constraints, foreignKeys, foreignData, showForeign.checked, selectForeign.checked, null);
}

function insertRow(columns, constraints, foreignKeys, foreignData, showForeign, selectForeign, data) {
    let row = document.createElement('tr');
    let cell = row.insertCell();
    cell.style.display = "none";
    cell.innerHTML = data?JSON.stringify(data):'{}';
    for (let column in columns) {
        let cell = row.insertCell();
        if (selectForeign && constraints[column] === "FOREIGN KEY") {
            cell.appendChild(
                getForeignSelect(data?data[column]:null, foreignKeys[column], foreignData[column], showForeign)
            );
            continue;
        }

        let input = document.createElement('input');
        let type = getInputType(columns[column]);
        input.type = type;
        input.onchange = () => {changed=true};
        if (!type) {
            input.disabled = true;
        }
        if (data) {
            input.value = data[column];
        }
        cell.appendChild(input);
    }
    if (data) {
        addDeleteButton(row);
    }
    else {
        row.onclick = () => addRow(row);
    }
    table.appendChild(row);
}

function addDeleteButton(row) {
    let cell = row.insertCell();
    let button = document.createElement('input');
    button.type = 'button';
    button.value = 'X';
    button.onclick = () => {deleteRow(row)};
    cell.appendChild(button);
}

function getInputType(type) {
    switch (type) {
        case "character varying":
        case "character":
        case "bpchar":
        case "text":
            return 'text';
        case "bigint":
        case "integer":
        case "smallint":
            return 'number';
        case "boolean":
            return 'checkbox';
        case "date":
            return 'date';
        case "timestamp":
        case "time":
            return 'datetime-local';
        case "bytea":
            return 'file';
        default:
            return '';
    }
}
function getForeignSelect(column, foreignKey, foreignData, showForeign) {
    let result = document.createElement('select');
    for (let subrow of foreignData) {
        let text;
        if (showForeign) {
            text = Object.values(subrow).join(' | ');
        }
        else {
            let diff = {};
            Object.assign(diff, subrow);
            delete diff[foreignKey["column"]];
            text = Object.values(diff).join(' | ');
        }
        let selected = subrow[foreignKey["column"]] === column;
        result.options.add(
            new Option(text, subrow[foreignKey["column"]], selected, selected)
        );
    }
    return result;
}
function deleteRow(rowToDelete) {
    let rowData = getOriginalData(rowToDelete);

    for (let i = 1; i<table.rows.length; i++) {
        let row = table.rows[i];
        // noinspection EqualityComparisonWithCoercionJS
        if (JSON.stringify(getOriginalData(row)) != JSON.stringify(rowData)) {
            continue;
        }
        table.deleteRow(i);

        if (hasOriginalData(row)) {
            toDelete.push(rowData);
        }

        return;
    }
}
function addRow(row) {
    addDeleteButton(row);
    addEmptyRow();
    row.onclick = null;
}
function hasOriginalData(row) {
    return !isEmpty(JSON.parse(row.cells[0].innerHTML));
}
function getOriginalData(row) {
    let data = JSON.parse(row.cells[0].innerHTML);
    if (isEmpty(data)) {
        data = getCurrentData(row);
    }

    return data;
}
function getCurrentData(row) {
    let data = {};
    let columnNames = Object.keys(columns);
    for (let i = 0; i < columnNames.length; i++) {
        data[columnNames[i]] = parseValue(row.cells[i+1].children[0].value, columns[columnNames[i]]);
    }

    return data;
}

function parseValue(value, type) {
    value = value===null?"":value
    switch (type) {
        case "bytea":  // IDK what to do with it
        case "character varying":
        case "character":
        case "bpchar":
        case "text":
            return `${value}`;
        case "integer":
        case "smallint":
        case "bigint":
            return parseInt(value);
        case "boolean":
            return value === "true";
        case "date":
            return Date.parse(value);
        case "timestamp":
        case "time":
            return Date.parse(value);
    }
}

function hasChanged(row) {
    return !(
        (JSON.stringify(getCurrentData(row)) === JSON.stringify(getOriginalData(row)))
        && hasOriginalData(row)
    );
}

function isEmpty(obj) {
    for (const prop in obj) {
        if (Object.hasOwn(obj, prop)) {
            return false;
        }
    }

    return true;
}

function sendData() {
    if(!confirm("Are you sure you want to save the changes?")) return
    debug.innerHTML = '';
    let toAdd = [];
    let toUpdate = [];

    for (let row of [...table.rows].slice(1, -1)) {
        let currData = getCurrentData(row);
        if (!hasChanged(row)) {
            continue;
        }
        if (!hasOriginalData(row)) {
            toAdd.push(currData);
            continue;
        }
        toUpdate.push([getOriginalData(row), currData]);
    }

    let request = new XMLHttpRequest();
    request.open("POST", `/update.php?table=${selectedTable.value}`, false);
    request.send(JSON.stringify(
        {
            toAdd: toAdd,
            toUpdate: toUpdate,
            toDelete: toDelete
        }
    ));
    if (request.status === 200) {
        alert("Changes saved");
        changed = false;
        location.reload();
    } else {
        if (confirm(`Something went wrong: ${request.status} ${request.statusText}\nShow debug?`)){
            debug.innerHTML = request.responseText.replaceAll('\n', '<br>').replaceAll('  ', '&nbsp;&nbsp;');
            debug.style.display = 'block';
        }
    }
}


function deleteUnsavedData(event) {
    if (changed) {
        event.returnValue = true;
        event.preventDefault();
    }
}
window.onbeforeunload = deleteUnsavedData;

fillTable();
addEmptyRow();

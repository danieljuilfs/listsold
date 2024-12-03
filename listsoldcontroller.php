
<?php 
include 'listsoldmodel.php';

if (isset($_POST['target'])) {
    if ($_POST['target'] === 'initial') {
        GetVendors(1); 
    }
    else if($_POST['target'] != 'none' ){ 
        GetVendors($_POST['target']); 
    }
}

if (isset($_POST['pagination'])) {
    if ($_POST['pagination'] === 'load') {
        CalcPages(); 
    }
}

if (isset($_POST['user'])) {
    if ($_POST['user'] != 'none' && ($_POST['page']) != 'null') {
        LoadItems($_POST['user'], $_POST['page']);
    }
}
if (isset($_POST['itemname']) && ($_POST['itemid'])) {
    echo 'itemname: ' . $_POST['itemname'] . ' ID: ' . $_POST['itemid']; 
   if ($_POST['itemname'] != 0 and ($_POST['itemid'] != 0)) {
        DisplayItem($_POST['itemname'], $_POST['itemid']); 
    }
}

if (isset($_POST['playername']) and $_POST['playername'] != 'none') {
    LoadItems($_POST['playername'], 1);
}
?>
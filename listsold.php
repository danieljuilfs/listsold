<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Dalaya Listsold</title>
    <script src="https://unpkg.com/htmx.org@2.0.3" integrity="sha384-0895/pl2MU10Hqc6jd4RvrthNlDiE9U1tWmX7WRESftEDRosgxNsQG/Ze9YMRzHq" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/listsold.css">
</head>
<body>
<div class="pageheader">
    <a href="https://dalaya.org" class="return">Main Site</a>
    <h1>Dalaya Listsold</h1> 
    <form hx-post="listsoldcontroller.php" class="search" hx-target=".items" hx-vals='{"target": "none", "pagination": "0", "user": "none", "page": "$i", "itemid": "0"}'>
        <input type="text" name="playername" placeholder="Character Search"/>
        <input type="submit" value="Search">
    </form>
</div>

<div class="main">
    <div class="vendorlist black">
        <div class="header">
            <h2>Character</h2>
            <h2>Description</h2>
        </div>
        <div class="vendors" hx-post="listsoldcontroller.php" hx-vals='{"target": "initial"}' hx-trigger="load once">

        </div>  
        <div class="pages" hx-post="listsoldcontroller.php" hx-vals='{"pagination": "load"}'  hx-trigger="load once">

        </div>
    </div>

    <div class="sales black">
        <div class="header2">
            <h2 class="leftpad">Item</h2>
            <h2 class="realign">Cost</h2>
        </div>
        <div class="items">

        </div>
        <div class="pages2" id ="pages2">

        </div>
        <div class="tooltip">

        </div>
    </div>
</div>


</body>
</html>


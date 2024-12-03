<?php 
//connect to the db. 
include '../common25/dbgmtools.php';
include 'spellid.php'; 
$amount = 20;
global $spells;

function makeconnection() {
    try {
        global $server, $username, $password;
        $connection = new PDO("mysql:host=" . $server. ";dbname=eq25", $username, $password); 
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $connection; 
    } 
    catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
}
    

function GetVendors($pagenumber) {
    $connection = makeconnection(); 

    //amount of values displayed per page
    global $amount; 

    //offset calculation
    $offset = ($pagenumber - 1) * $amount; 
    if ($connection) {
        try {
            $statement = $connection->prepare('select character_data.name, sellers.charname, sellers.text from sellers join character_data on sellers.charname = character_data.name LIMIT :limit OFFSET :offset');
            $statement->bindvalue(':limit', $amount, PDO::PARAM_INT); 
            $statement->bindvalue(':offset', $offset, PDO::PARAM_INT);

            $statement->execute();

            $results = $statement->fetchAll(PDO::FETCH_ASSOC);

            foreach($results as $row) {
                echo "<div class='vendorname'><a href='' class='link' hx-post='listsoldcontroller.php' hx-vals='{\"user\": \"$row[name]\", \"target\": \"none\", \"pagination\": \"0\", \"page\": \"1\", \"itemname\": \"0\", \"playername\": \"none\"}' hx-target='.items'>$row[charname]</a></div><div class='description'>$row[text]</div>";
            }

        }
        catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }
}

function CalcPages() {
    $connection = makeconnection(); 
    global $amount;
    
    $count = $connection->prepare("Select COUNT(*) from sellers"); 
    $count->execute();
    $total = $count->fetchColumn(); 

    $pagecount = ceil($total/$amount); 

    for($i=1; $i <= $pagecount; $i++){
        echo "<a href='' class='numbering link' hx-target='.vendors' hx-vals='{\"target\": \"$i\", \"pagination\": \"0\", \"user\": \"none\", \"page\": \"none\", \"itemid\": \"0\", \"playername\": \"none\"}' hx-post='listsoldcontroller.php'>[$i]</a>";
    }
}

function LoadItems($user, $pagenumber) {
    $connection = makeconnection(); 
    global $amount; 

    $getcharid = $connection->prepare("SELECT character_data.id from character_data where character_data.name = :user");
    $getcharid->bindparam(':user', $user, PDO::PARAM_STR); 
    $getcharid->execute();
    $result = $getcharid->fetch(); 
    $charid = $result['id']; 

    //calculate the offset for displaying pages
    $offset = ($pagenumber - 1) * $amount; 

    $items = $connection->prepare("SELECT inventory.itemid, items_patch.name, inventory.augment1, inventory.augment2, inventory.charges, inventory.price_listed FROM inventory JOIN items_patch ON inventory.itemid = items_patch.id JOIN character_data ON inventory.charid = character_data.id WHERE price_listed > 0 and character_data.id = :charid LIMIT :limit OFFSET :offset"); 
    $items->bindParam(':charid', $charid, PDO::PARAM_STR);
    $items->bindvalue(':limit', $amount, PDO::PARAM_INT); 
    $items->bindvalue(':offset', $offset, PDO::PARAM_INT);
    $items->execute();


    $results = $items->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach($results as $row){
            echo "<div class='item'>";
            echo 'item id is ' . $row['itemid'] . " name is " . $row['name']; 
            echo "<a href='#' class='itemlink link' hx-post='listsoldcontroller.php' hx-vals='" . htmlspecialchars(json_encode(["target" => "none", "pagination" => "0", "user" => "none", "itemid" => $row['itemid'], "page" => "null", "itemname" => $row['name'], "playername" => "none"]), ENT_QUOTES, 'UTF-8') . "' hx-target='.tooltip'>" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "</a>";
            if ($row['augment1'] > 0) echo "<span class='augment'> [" . GetAugType($row['augment1']) . "]</span>";
            if ($row['augment2'] > 0) echo " <span class='augment'>[" . GetAugType($row['augment2']) . "]</span>";
            if ($row['charges'] > 1 && $row['charges'] != 75) echo " x" . $row['charges']; 
            echo "</div><div class='price'>{$row['price_listed']}p</div>"; 
        }
    }
    else echo "<div class='item'>This user no longer has any items for sale</div>";

    //getting the pagecount
    $pages = $connection->prepare("SELECT COUNT(inventory.price_listed) FROM inventory JOIN character_data ON inventory.charid = character_data.id WHERE price_listed > 0 and character_data.id = :charid"); 
    $pages->bindParam(':charid', $user, PDO::PARAM_STR);
    $pages->execute(); 
    $numpages = $pages->fetchcolumn(); 

    //get the maximum number of pages.
    $pagecount = ceil($numpages/$amount); 
    echo "<div class='pages2' id='pages2' hx-swap-oob='true'>";
    if ($pagecount > 1) {
        for($i=1; $i <= $pagecount; $i++){
            echo "<a href='' class='numbering link'  hx-target='.items' hx-vals='{\"target\": \"none\", \"pagination\": \"0\", \"user\": \"$user\", \"page\": \"$i\", \itemname\": \"0\", \"playername\": \"none\"}' hx-post='listsoldcontroller.php'>[$i]</a>";
        }
    }
    echo "</div>"; 
}

function DisplayItem($itemname, $itemid) {
    $connection = makeconnection(); 
    global $spells; 
    echo 'inside display items name is: ' . $itemname . " id is: " . $itemid;  
    $item = $connection->prepare("SELECT items_patch.id, items_patch.name, items_patch.adex, items_patch.accuracy, items_patch.icon, items_patch.strikethrough, items_patch.spellshield, items_patch.shielding, items_patch.avoidance,  items_patch.weight, items_patch.maxcharges, items_patch.casttime, items_patch.size, items_patch.effecttype,  items_patch.aagi, items_patch.stunresist, items_patch.itemtype, items_patch.slots, items_patch.pendingloreflag, items_patch.magic, items_patch.mana, items_patch.ac, items_patch.acha, items_patch.aint, items_patch.asta, items_patch.astr, items_patch.augslot1type, items_patch.augslot2type, items_patch.augslot3type, items_patch.augslot4type, items_patch.augslot5type, items_patch.awis, items_patch.banedmgamt, items_patch.banedmgbody, items_patch.banedmgrace, items_patch.bardtype, items_patch.bardvalue, items_patch.classes, items_patch.combateffects, items_patch.cr, items_patch.damage, items_patch.delay, items_patch.dr, items_patch.elemdmgtype, items_patch.elemdmgamt, items_patch.focusid, items_patch.fr, items_patch.hp, items_patch.hasteproclvl, items_patch.magic, items_patch.mr, items_patch.nodrop, items_patch.pr, items_patch.races, items_patch.reclevel, items_patch.slots, items_patch.spellid, items_patch.skillmodtype, items_patch.skillmodvalue FROM items_patch where items_patch.name = :name and items_patch.id = :itemid");
    $item->bindParam(':name', $itemname, PDO::PARAM_STR);
    $item->bindParam(':itemid', $itemid, PDO::PARAM_STR);
    $item->execute();
    $results = $item->fetchAll(PDO::FETCH_ASSOC);

    foreach($results as $row) {
        
        echo "<div class='icon'><img src='images/" . $row['icon'] . ".png'></img>'</div>";
        echo "<div class='forsale'>";
        echo "<div class='name'>{$row['name']}</div>";

        //magic/lore/nodrop
        echo "<div class='properties'>";
        if ($row['pendingloreflag'] > 0)
            echo "[LORE] "; 
        if ($row['magic'] > 0)
            echo "[MAGIC] "; 
        echo "</div>"; 

        //item slots
        if ($row['slots'] > 0 ) {
            echo "<div class='slots'> Slot: "; 
            $slots =  GetSlot($row['slots']); 
            echo $slots; 
            echo "</div>"; 
        }
    
        //weapon damage
        echo "<div class='damage'>"; 
        if ($row['damage'] != 0 && $row['delay'] != 0 ) {
            $skill = GetSkillType($row['itemtype']); 
            echo 'Skill: ' . $skill . ' Damage: ' . $row['damage'] . ' Delay: ' . $row['delay'] . ' '; 
        }
        //ele damage
        if ($row['elemdmgtype'] > 0) {
            $damagetype = GetDamageType($row['elemdmgtype']);
            echo $damagetype . ' DMG: ' . $row['elemdmgamt'] . ' '; 
        }
        //bane damage
        if ($row['banedmgbody'] > 0) {
            $bodytype = GetBodyType($row['banedmgbody']); 
            echo 'Bane DMG: ' . $bodytype . ' ' . $row['banedmgamt'] . ' '; 
        }
        echo "</div>"; 

        //ac
        if ($row['ac'] > 0) echo "AC: " . $row['ac']; 

        //instrument modifier
        if ($row['bardtype'] != 0) {
            $instrument = GetBardType($row['bardtype']); 
            $instrument .= ' ' . floatval($row['bardvalue'] /10) ;
            echo "<div class='instrumentmod'>Instrument Modifier: " . $instrument ;
        }
        //focus effect
        if ($row['focusid'] > 0) echo "<div class = focus>Focus Effect: " . $spells[$row['focusid']] ; 
        
        //haste
        if ($row['hasteproclvl'] > 0) echo "<div class='haste'>Haste: " . $row['hasteproclvl'] . '%'; 

        //effect
        if ($row['spellid'] > 0 && $row['spellid'] < 65535) {
            $effecttype = $spells[$row['spellid']] . ' ('; 
            $effecttype .= GetEffectType($row['effecttype']); 
            //addcharges if its an item with charges
            if ($row['effecttype'] == 3) $effecttype .= ': ' . $row['maxcharges']; 
            if ($row['casttime'] > 0 && ($row['effecttype'] == 1 || $row['effecttype'] == 3 || $row['effecttype'] == 5)) $effecttype .= ' Casting Time: ' . floatval($row['casttime'])/1000; 
            echo "<div class='effect'> Effect: " . $effecttype . ')'; 
        };

        //skillmods
        if ($row['skillmodvalue'] > 0) {
            $type = GetSkillMod($row['skillmodtype']);
            echo "<div class='skill'>Skill Mod: " . $type . ' +' . $row['skillmodvalue']; 
        }

        //stats
        echo "<div class='stats'>"; 
        if ($row['astr'] > 0) echo 'STR: +' . $row['astr'] . ' '; 
        if ($row['asta'] > 0) echo 'STA: +' . $row['asta'] . ' '; 
        if ($row['aagi'] > 0) echo 'AGI: +' . $row['aagi'] . ' ';
        echo 'dex is: ' . $row['adex']; 
        if ($row['adex'] > 0) echo 'DEX: +' . $row['adex'] . ' ';
        if ($row['awis'] > 0) echo 'WIS: +' . $row['awis'] . ' ';
        if ($row['aint'] > 0) echo 'INT: +' . $row['aint'] . ' ';
        if ($row['acha'] > 0) echo 'CHA: +' . $row['acha'] . ' ';
        if ($row['hp'] > 0) echo 'HP: +' . $row['hp'] . ' '; 
        if ($row['mana'] > 0) echo 'Mana: +' . $row['mana'] . ' '; 
        echo "</div>";

        //resistances
        echo "<div class ='resistances'>";
        if ($row['mr'] > 0) echo "MR: +" . $row['mr'] . ' '; 
        if ($row['fr'] > 0) echo "FR: +" . $row['fr'] . ' ';
        if ($row['cr'] > 0) echo "CR: +" . $row['cr'] . ' ';
        if ($row['pr'] > 0) echo "PR: +" . $row['pr'] . ' ';
        if ($row['dr'] > 0) echo "DR: +" . $row['dr'] . ' ';    
        echo "</div>"; 

        //secondary stats
        echo "<div class='secondaries'>";
        //damage reduction = shielding
        if ($row['shielding'] != 0) {
            if ($row['shielding'] > 0) echo "Damage Reduction: +" . $row['shielding'] . ' ';
            else echo "Damage Reduction: " . $row['shielding'] . ' ';
        }
        //crit = strikethrough
        if ($row['strikethrough'] != 0) {
            //have to handle positive/negatives since only negatives are signed in the db
            if ($row['strikethrough'] > 0) echo "Crit Strike: +" . $row['strikethrough'] . ' ';
            else echo "Crit Strike: " . $row['strikethrough'] . ' ';
        }
        //mindshield = spellshield
        if ($row['spellshield'] != 0) {
            if ($row['spellshield'] > 0 ) echo "Mindshield: +" . $row['spellshield'] . ' ';
            else echo "Mindshield: " . $row['spellshield'] . ' ';
        }
        //spellward = avoidance
        if ($row['avoidance'] != 0){
            if ($row['avoidance'] > 0) echo "Spellward: +" . $row['avoidance'] . ' '; 
            else echo "Spellward: " . $row['avoidance']; 
        } 
        //aggression = accuracy
        if ($row['accuracy'] != 0) {
            if ($row['accuracy'] > 0) echo "Aggression: +" . $row['accuracy'] . ' ';
            else echo "Aggression: " . $row['accuracy'];
        }
            //stun resist = stunresist
        if ($row['stunresist'] != 0) {
            if ($row['stunresist'] > 0) echo "Stun Resist: +" . $row['stunresist'] . ' ';
            else echo "Stun Resist: " . $row['stunresist'] . ' ';
        }
        //ft = combateffects
        if  (is_numeric($row['combateffects']) && $row['combateffects'] != 0) 
            if ($row['combateffects'] > 0) echo "Flowing Thought: +" . $row['combateffects'] . ' ';
            else echo "Flowing Thought: " . $row['combateffects'] . ' ';
        //reclevel
        if ($row['reclevel'] > 0) echo '<div>Recommended Level: ' . $row['reclevel'] . '</div>'; 

        //weight + size
        echo "<div class='size'>Weight: " . floatval($row['weight']/10) . ' Size: ' . GetSize($row['size']) . '</div>';

        //class and race
        if ($row['classes'] > 0) echo "<div class='classes'>Class: " . GetClass($row['classes']) . '</div>';
        if ($row['races'] > 0) echo "<div class='races'>Race: " . GetRaces($row['races']) . " </div>";

        //augments
        if ($row['augslot2type'] > 0) echo "<div class='aug'>Aug Slot: Type " . $row['augslot2type'];
        if ($row['augslot3type'] > 0) echo "<div class='aug'>Aug Slot: Type " . $row['augslot3type'];

        echo "</div>";
    }
}

function GetAugType($augmentid) {
    $connection = makeconnection(); 
    $augment = $connection->prepare("SELECT items_patch.name FROM items_patch where items_patch.id = :itemid");
    $augment->bindParam(':itemid', $augmentid, PDO::PARAM_STR);
    $augment->execute();
    return $augment->fetchColumn(); 

}

function GetCharacterId($name) {
  $connection = makeconnection(); 
  $playerid = $connection->prepare("SELECT character_data.id from character_data where name = :name"); 
  $playerid->bindparam(':name', $name, PDO::PARAM_STR); 
  $playerid->execute(); 
  $results = $playerid->fetch(); 

  //echo 'character id is' . $results['id']; 
  //echo 'size of results is' . sizeof($results); 
  if ($results) LoadItems($results['id'], 1);
  else echo '<div class>Character Name Does Not Exist</div>';
}

function GetSlot($slot) {
        $itemslot = '';
        if ( $slot & 1 ) $itemslot .= ' Charm ';
        if ( ($slot & 2) && ($slot & 16) ) $itemslot .= ' Ear ';
        elseif ( $slot & 2 ) $itemslot .= 'Left Ear ';
        elseif ( $slot & 16 ) $itemslot .= 'Right Ear ';
        if ( $slot & 4 ) $itemslot .= 'Head ';
        if ( $slot & 8 ) $itemslot .= 'Face ';
        if ( $slot & 32 ) $itemslot .= 'Neck ';
        if ( $slot & 64 ) $itemslot .= 'Shoulders ';
        if ( $slot & 128 ) $itemslot .= 'Arms ';
        if ( $slot & 256 ) $itemslot .= 'Back ';
        if ( ($slot & 512) && ($slot & 1024) ) $itemslot .= 'Wrist ';
        elseif ( $slot & 512 ) $itemslot .= 'Left Wrist ';
        elseif ( $slot & 1024 ) $itemslot .= 'Right Wrist ';
        if ( $slot & 2048 ) $itemslot .= 'Range ';
        if ( $slot & 4096 ) $itemslot .= 'Hands ';
        if ( $slot & 8192 ) $itemslot .= 'Primary ';
        if ( $slot & 16384 ) $itemslot .= 'Secondary ';
        if ( ($slot & 32768) && ($slot & 65536) ) $itemslot .= 'Fingers ';
        elseif ( $slot & 32768 ) $itemslot .= 'Left Fingers ';
        elseif ( $slot & 65536 ) $itemslot .= 'Right Fingers ';
        if ( $slot & 131072 ) $itemslot .= 'Chest ';
        if ( $slot & 262144 ) $itemslot .= 'Legs ';
        if ( $slot & 524288 ) $itemslot .= 'Feet ';
        if ( $slot & 1048576 ) $itemslot .= 'Waist ';
        if ( $slot & 2097152 ) $itemslot .= 'Ammo ';
        return $itemslot; 
}

function GetClass($classid) {
    $class= '';
		
    if ( $classid == 65535 ) $class .= 'ALL ';
    else {
        if ($classid & 1 ) $class .= 'WAR ';
        if ($classid & 2 ) $class .= 'CLR ';
        if ($classid & 4 ) $class .= 'PAL ';
        if ($classid & 8 ) $class .= 'RNG ';
        if ($classid & 16)  $class .= 'SHD ';
        if ($classid & 32 ) $class .= 'DRU ';
        if ($classid & 64 ) $class .= 'MNK ';
        if ($classid & 128 ) $class .= 'BRD ';
        if ($classid & 256 ) $class .= 'ROG ';
        if ($classid & 512 ) $class .= 'SHM ';
        if ($classid & 1024 ) $class .= 'NEC ';
        if ($classid & 2048 ) $class .= 'WIZ ';
        if ($classid & 4096 ) $class .= 'MAG ';
        if ($classid & 8192 ) $class .= 'ENC ';
        if ($classid & 16384 ) $class .= 'BST ';
    }
    return $class; 
}

function GetRaces($raceid) {

    $races = '';

    if ($raceid == 65535 ) $races .= 'ALL ';
    else {
        if ($raceid & 1 ) $races .= 'HUM ';
        if ($raceid & 2 ) $races .= 'BAR ';
        if ($raceid & 4 ) $races .= 'ERU ';
        if ($raceid & 8 ) $races .= 'ELF ';
        if ($raceid & 16 ) $races .= 'HIE ';
        if ($raceid & 32 ) $races .= 'DEF ';
        if ($raceid & 64 ) $races .= 'HEF ';
        if ($raceid & 128 ) $races .= 'DWF ';
        if ($raceid & 256 ) $races .= 'TRL ';
        if ($raceid & 512 ) $races .= 'OGR ';
        if ($raceid & 1024 ) $races .= 'HFL ';
        if ($raceid & 2048 ) $races .= 'GNM ';
        if ($raceid & 4096 ) $races .= 'IKS ';
        if ($raceid & 8192 ) $races .= 'VAH ';
        if ($raceid & 16384 ) $races .= 'FRG ';
    }
    return $races; 
}

function GetSkillType($itemtype) {
    $weapontype = '';
    switch ($itemtype) {
        case 0: 
            $weapontype = '1H Slash';
            break;
        case 1:
            $weapontype = '2H Slash';
            break;
        case 2:
            $weapontype = 'Piercing';
            break;
        case 3:
            $weapontype = '1H Blunt';
            break;
        case 4:
            $weapontype = '2H Blunt';
            break;
        case 5:
            $weapontype = 'Archery';
            break;
        case 7: 
            $weapontype = 'Throwing';
            break;
        case 19: 
            $weapontype = 'Hand to Hand';
    } 
    return $weapontype; 
}

function GetDamageType($elemdmg) {
    $elemtype = '';
    switch ($elemdmg) {
        case 1:
            $elemtype = 'Magic';
            break;
        case 2;
            $elemtype = 'Fire';
            break;
        case 3: 
            $elemtype = 'Cold';
            break;
        case 4:
            $elemtype = 'Poison';
            break;
        case 5:
            $elemtype = 'Disease';
            break;
        case 6:
            $elemtype = 'Chromatic';
            break;
        case 7:
            $elemtype = 'Prismatic';
            break; 
    }

    return $elemtype;
}

function GetBodyType($typeid) {
    $bodytype ='';

    switch ($typeid) {
        case 1: 
            $bodytype = 'Humanoid Monster';
            break; 
        case 2:
            $bodytype = 'Ancient Race';
            break; 
        case 3:
            $bodytype = 'Undead';
            break; 
        case 4:
            $bodytype = 'Greatkin';
            break; 
        case 5:    
            $bodytype = 'Construct/Clockwork';
            break; 
        case 6:
            $bodytype = 'Chaotic';
            break; 
        case 7:
            $bodytype = 'Young Race';
            break; 
        case 13:
            $bodytype = 'Magic';
            break; 
        case 14:
            $bodytype = 'Reptile/Amphibian';
            break; 
        case 21:
            $bodytype = 'Nature';
            break; 
        case 22:    
            $bodytype = 'Insect/Arachnid';
            break; 
        case 23:
            $bodytype = 'Monster';
            break; 
        case 24:    
            $bodytype = 'Planar Elemental';
            break; 
        case 26:
            $bodytype = 'Dragonkin';
            break; 
        case 28:    
            $bodytype = 'Lesser Elemental';
            break; 
    }
    return $bodytype; 
}

function GetSize($sizeid) {
    $size = '';

    switch ($sizeid) {
        case 0:
            $size = 'Tiny';
            break;
        case 1:
            $size = 'Small';
            break;
        case 2:
            $size = 'Medium';
            break;
        case 3:
            $size = 'Large';
            break;
        case 4: 
            $size = 'Giant'; 
            break;
    }

    return $size;
}

function GetEffectType($effectid) {
    $effecttype = '';
    switch ($effectid) {
        case 0:
            $effecttype = 'Proc';
            break;
        case 1: 
        case 5: 
            $effecttype = 'Clicky';
            break; 
        case 2: 
            $effecttype = 'Worn';
            break;
        case 3: 
            $effecttype = 'Clicky, Charges';
            break;
        case 4:
            $effecttype = 'Must Wear Clicky';
            break;
    }

    return $effecttype; 
}

function GetSkillMod($skillid) {
    $skilltype = '';
    switch ($skillid) {
        case 0:
            $skilltype = '1H Blunt';
            break;
          case 1:
            $skilltype = '1H Slash';
            break;
          case 2:
            $skilltype = '2H Blunt';
            break;
          case 3:
            $skilltype = '2H Slash';
            break;
          case 4:
            $skilltype = 'Abjuration';
            break;
          case 5:
            $skilltype = 'Alteration';
            break;
          case 6:
            $skilltype = 'Apply Poison (unused)';
            break;
          case 7:
            $skilltype = 'Archery';
            break;
          case 8:
            $skilltype = 'Backstab';
            break;
          case 9:
            $skilltype = 'Bind Wound';
            break;
          case 10:
            $skilltype = 'Bash';
            break;
          case 11:
            $skilltype = 'Block';
            break;
          case 12:
            $skilltype = 'Brass Instruments';
            break;
          case 13:
            $skilltype = 'Channeling';
            break;
          case 14:
            $skilltype = 'Conjuration';
            break;
          case 15:
            $skilltype = 'Defense';
            break;
          case 16:
            $skilltype = 'Disarm';
            break;
          case 17:
            $skilltype = 'Disarm/Set Traps';
            break;
          case 18:
            $skilltype = 'Divination';
            break;
          case 19:
            $skilltype = 'Dodge';
            break;
          case 20:
            $skilltype = 'Double Attack';
            break;
          case 21:
            $skilltype = 'Dragon Punch/Tail Rake';
            break;
          case 22:
            $skilltype = 'Dual Wield';
            break;
          case 23:
            $skilltype = 'Eagle Strike';
            break;
          case 24:
            $skilltype = 'Evocation';
            break;
          case 25:
            $skilltype = 'Feign Death';
            break;
          case 26:
            $skilltype = 'Flying Kick';
            break;
          case 27:
            $skilltype = 'Forage';
            break;
          case 28:
            $skilltype = 'Hand to Hand';
            break;
          case 29:
            $skilltype = 'Hide';
            break;
          case 30:
            $skilltype = 'Kick';
            break;
          case 31:
            $skilltype = 'Meditate';
            break;
          case 32:
            $skilltype = 'Mend';
            break;
          case 33:
            $skilltype = 'Offense';
            break;
          case 34:
            $skilltype = 'Parry';
            break;
          case 35:
            $skilltype = 'Pick Locks';
            break;
          case 36:
            $skilltype = 'Piercing';
            break;
          case 37:
            $skilltype = 'Riposte';
            break;
          case 38:
            $skilltype = 'Round Kick';
            break;
          case 39:
            $skilltype = 'Safe Fall';
            break;
          case 40:
            $skilltype = 'Sense Heading';
            break;
          case 41:
            $skilltype = 'Singing';
            break;
          case 42:
            $skilltype = 'Sneak';
            break;
          case 43:
            $skilltype = 'Specialize Blade/Strike';
            break;
          case 44:
            $skilltype = 'Specialize Blunt/Avoidance';
            break;
          case 45:
            $skilltype = 'Specialize Sight/Warding';
            break;
          case 46:
            $skilltype = 'Specialize Energy/Defense';
            break;
          case 47:
            $skilltype = 'Specialize Focus/Mind';
            break;
          case 48:
            $skilltype = 'Pick Pockets';
            break;
          case 49:
            $skilltype = 'Stringed Instruments';
            break;
          case 50:
            $skilltype = 'Swimming';
            break;
          case 51:
            $skilltype = 'Throwing';
            break;
          case 52:
            $skilltype = 'Tiger Claw';
            break;
          case 53:
            $skilltype = 'Tracking';
            break;
          case 54:
            $skilltype = 'Wind Instruments';
            break;
          case 55:
            $skilltype = 'Fishing';
            break;
          case 56:
            $skilltype = 'Make Poison';
            break;
          case 57:
            $skilltype = 'Tinkering (unused)';
            break;
          case 58:
            $skilltype = 'Research (unused)';
            break;
          case 59:
            $skilltype = 'Alchemy';
            break;
          case 60:
            $skilltype = 'Baking';
            break;
          case 61:
            $skilltype = 'Tailoring';
            break;
          case 62:
            $skilltype = 'Sense Traps';
            break;
          case 63:
            $skilltype = 'Blacksmithing';
            break;
          case 64:
            $skilltype = 'Fletching';
            break;
          case 65:
            $skilltype = 'Brewing';
            break;
          case 66:
            $skilltype = 'Alcohol Tolerance';
            break;
          case 67:
            $skilltype = 'Begging (unused)';
            break;
          case 68:
            $skilltype = 'Jewelry Making';
            break;
          case 69:
            $skilltype = 'Pottery';
            break;
          case 70:
            $skilltype = 'Percussion Instruments';
            break;
          case 71:
            $skilltype = 'Intimidation';
            break;
          case 72:
            $skilltype = 'Mining';
            break;
          case 73:
            $skilltype = 'Taunt';
            break;
          default:
            $skilltype = 'Unknown Skill';
            break;
    }   
        return $skilltype; 
}

function GetBardType($instrument) {
    $bardtype = ' ';
    switch($instrument) {
        case 23:
            $bardtype = 'Wind Instruments';
            break;
        case 24: 
            $bardtype = 'String Instruments';
            break;
        case 25:
            $bardtype = 'Brass Instruments';
            break;
        case 26:
            $bardtype = 'Percussion Instruments';
            break;
        case 50:
            $bardtype =  'Singing';
            break;
        case 51:
            $bardtype = 'All Instruments';
            break;
    }
    return $bardtype;
}

?>


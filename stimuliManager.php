<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title></title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript">
      var stimuliData;
      var set;
      var groupOptions = {
        def : "Group Actions",
        addAbove : "Add Group Above",
        addBelow : "Add Group Below",
        remove : "Remove Group",
        appendStimulus : "Append Stimulus"
      };
      function requestStimuliSet (parameters) {
        if (window.XMLHttpRequest)
        {// code for IE7+, Firefox, Chrome, Opera, Safari
          xmlhttp=new XMLHttpRequest();
        }
        else
        {// code for IE6, IE5
          xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function (aEvt) {
          if (this.readyState == 4) {
            if(this.status !== 200) {
              location.href="servererror.php?status=" + this.status + "&statusText=" + encodeURIComponent(this.statusText);
            } else {
              var data = JSON.parse(this.responseText);
              $('#responseCount').text(data.responseCount);
              if (data.stimuliGroups) {
                var num = data.stimuliGroups.length;
                stimuliData = data.stimuliGroups;
                for (var i=0; i < num; i++) {
                  insertGroup(i,data.stimuliGroups[i].groupName,data.stimuliGroups[i].stimuli,data.stimuliGroups[i].randomize,data.stimuliGroups[i].group_id);
                }
              } else {
                
              }
            }
          }
        };
        xmlhttp.open("POST","requestStimuliSet.php",true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.setRequestHeader("Content-length", parameters.length);
        xmlhttp.setRequestHeader("Connection", "close");
        xmlhttp.send(parameters);
      }
      function insertGroup (afterPosition,name,content,randomize,groupId) {
        var $row = $('#stimuliBody').children().eq(afterPosition);
        var newRow = _createGroupRow(name,content,randomize,groupId,afterPosition);
        if ($row.size() === 0) {
          $('#stimuliBody').append(newRow);
        } else {
          $row.after(newRow);
        }
      }
      function _createGroupRow (name,content,randomize,groupId,groupNum) {
        var body = $('<tr>').appendTo($('<tbody>')).append('<td>').append($('<td>').attr('colspan','2').append(_createGroupContent(content)));
        var disclose = $('<input>').attr('type','image').click(function () {discloseGroupToggle(body)}).attr('src','disclosureTriangle.png');
        var $actions = $('<select>');
        $.each(groupOptions,function (val,text) {
          $actions.append(
            $('<option></option>').val(val).html(text)
          );
        });
        $actions.change(function () {handleGroupAction(this);});
        var head = $('<thead>').append($('<th>').append(disclose)).append('<th>' + name + '</th>').append($('<th>' + groupId + '</th>').attr('style','display:none')).append($('<th>').append($actions)).append($("<th>").append($('<input>').attr("type","checkbox").attr("checked",((randomize === "1") ? true : false)).click(function () {toggleGroupRandomization(groupNum,groupId)})).append(" Randomize"));
        var table = $('<table>').append(head).append(body);
        var tableRow = $('<tr>').append(table);
        return tableRow;
      }
      function _createGroupContent (content) {
        var table = $('<table>');
        for (var i = 0; i < content.length; i++) {
          var tr_elem = _createStimulusRow(content[i]);
          $(table).append($(tr_elem));
        }
        return table;
      }
      function toggleGroupRandomization(groupNum,groupId) {
        $.post("toggleGroupRandomization.php",{
                group_id:groupId,
                randomize:(stimuliData[groupNum].randomize === "0" ? "1" : "0")
              });
        stimuliData[groupNum].randomize = stimuliData[groupNum].randomize === "0" ? "1" : "0";
      }
      function removeGroup($groupRow) {
        $.post(
          "removeGroup.php",{
            group:stimuliData[$groupRow.index()].group_id
          }
        );
        stimuliData.splice($groupRow.index(),1);
        $groupRow.remove();
      }
      function replaceRowWithNewStimulus($row) {
        $.post("addNewStimulus.php",{
                set:set,
                group:parseInt($row.parents('table').eq(1).find('th').eq(2).text(),10)
              }, function (received_data) {
                var data = JSON.parse(received_data)[0];
                stimuliData[$row.parents('table').eq(1).index()].stimuli.splice(0,0,data);
                $row.replaceWith(createStimulusRow(data.stim_id,data.category1,data.category2,data.subcategory1,data.subcategory2,data.word,data.correct_response,data.instruction));
            });
      }
      function handleGroupAction(selectBox) {
        switch (selectBox.selectedIndex) {
          case 0:
            alert("Default Group Action");
            selectBox.selectedIndex = 0;
            break;
          case 1:
            $.post("insertGroup.php",{
              set:set,
              position:$(selectBox).parent().parent().index(),
              below:"false"
            },function(receivedData) {
              var data = JSON.parse(receivedData);
              insertGroup($(selectBox).parent().parent().index(),data.name,data.stimuli,data.randomize,data.group_id);
              stimuliData.splice($(selectBox).parent().parent().index(),0,data);
            });
            selectBox.selectedIndex = 0;
            break;
          case 2:
            $.post("insertGroup.php",{
              set:set,
              position:$(selectBox).parent().parent().index(),
              below:"true"
            },function(receivedData) {
              var data = JSON.parse(receivedData);
              insertGroup($(selectBox).parent().parent().index(),data.name,data.stimuli,data.randomize,data.group_id);
              stimuliData.splice($(selectBox).parent().parent().index(),0,data);
            });
            selectBox.selectedIndex = 0;
            break;
          case 3:
            removeGroup($(selectBox).closest('tr'));
            selectBox.selectedIndex = 0;
            break;
          case 4:
            $.post("insertNewStimulus.php",{
                below:true,
                position:0,
                stim_set:set,
                group:selectBox.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.childNodes[0].childNodes[2].textContent
              }, function (received_data) {
                var data = JSON.parse(received_data)[0];
                stimuliData[$(selectBox).parent().parent().parent().parent().parent().parent().parent().parent().index()].stimuli.splice($(selectBox).parent().parent().index(),0,data);
                $(selectBox).parent().parent().after(createStimulusRow(data.stim_id,data.category1,data.category2,data.subcategory1,data.subcategory2,data.word,data.correct_response,data.instruction));
            });
            selectBox.selectedIndex = 0;
          default:
            selectBox.selectedIndex = 0;
            break;
        }
      }
      function _createStimulusRow (data) {
        return createStimulusRow(data.stim_id,data.category1,data.category2,data.subcategory1,data.subcategory2,data.word,data.correct_response,data.instruction);
      }
      function discloseGroupToggle($groupTable) {
        $groupTable.toggleClass("hidden");
      }
      function save_stimulus_row (stimuliRow) {
        var stimulusTable = stimuliRow.childNodes[1].childNodes[0];
        var poststr;
        if (stimulusTable.rows) {
          poststr = "leftCategory=" + encodeURI( stimulusTable.rows[0].cells[0].lastChild.value ) +
            "&rightCategory=" + encodeURI( stimulusTable.rows[0].cells[2].lastChild.value ) +
            "&subLeftCategory=" + encodeURI( stimulusTable.rows[1].cells[0].lastChild.value ) +
            "&subRightCategory=" + encodeURI( stimulusTable.rows[1].cells[1].lastChild.value ) +
            "&word=" + encodeURI( stimulusTable.rows[0].cells[1].lastChild.value ) +
            "&mask=" + encodeURI( (stimulusTable.rows[0].cells[3].childNodes[7].checked == true)?"1":"0") +
            "&stim_id=" + encodeURI(stimuliRow.childNodes[0].childNodes[0].alt);
        } else {
          poststr = "instruction=" + encodeURI( stimulusTable.textContent) +
            "&stim_id=" + encodeURI(stimuliRow.childNodes[0].childNodes[0].alt);
        }
        if (window.XMLHttpRequest)
        {// code for IE7+, Firefox, Chrome, Opera, Safari
          xmlhttp=new XMLHttpRequest();
        }
        else
        {// code for IE6, IE5
          xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function (aEvt) {
          if (this.readyState == 4) {
            if(this.status !== 200) {
              location.href="servererror.php?status=" + this.status + "&statusText=" + encodeURIComponent(this.statusText);
            } else {
              var stimuli = JSON.parse(this.responseText);
              i = 0;
              stimuliRow.parentNode.replaceChild(createStimulusRow(stimuli[i].stim_id,stimuli[i].category1,stimuli[i].category2,stimuli[i].subcategory1,stimuli[i].subcategory2,stimuli[i].word,stimuli[i].correct_response,stimuli[i].instruction),stimuliRow);
            }
          }
        };
        xmlhttp.open("POST","updateStimulus.php",true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.send(poststr);
      }
      function createStimulusRow(stim_id,cat1,cat2,subcat1,subcat2,word,correct,instruction) {
        var stimulusRow = document.createElement('tr');

        // id cell
        var idCell = stimulusRow.insertCell(0);
        idCell.appendChild(document.createTextNode(stim_id));

        //stimulus cell
        var stimulusCell = stimulusRow.insertCell(1);
        stimulusCell.appendChild(createStimulusTable(cat1,cat2,subcat1,subcat2,word,correct,instruction));

        // edit cell
        var editCell = stimulusRow.insertCell(2);
        var button = document.createElement('button');
        button.onclick = make_row_editable;
        button.innerHTML = "Edit";
        editCell.appendChild(button);

        //add remove cell
        var selectBoxCell = stimulusRow.insertCell(3);
        var selectBox = document.createElement('select');
        var noOption = document.createElement('option');
        noOption.appendChild(document.createTextNode("Actions"));
        var removeOption = document.createElement('option');
        removeOption.appendChild(document.createTextNode("Remove"));
        var addAboveOption = document.createElement('option');
        addAboveOption.appendChild(document.createTextNode("Add Row Above"));
        var addBelowOption = document.createElement('option');
        addBelowOption.appendChild(document.createTextNode("Add Row Below"));
        var copyOption = document.createElement('option');
        copyOption.appendChild(document.createTextNode("Copy"));
        selectBox.appendChild(noOption);
        selectBox.appendChild(removeOption);
        selectBox.appendChild(addAboveOption);
        selectBox.appendChild(addBelowOption);
        selectBox.onchange = function () {handleRowAction(selectBox);};
        selectBoxCell.appendChild(selectBox);
        return stimulusRow;
      }
      function handleRowAction (selectBox) {
        switch (selectBox.selectedIndex) {
          case 0:
            alert("no option");
            break;
          case 1:
            remove_row(selectBox.parentNode.parentNode);
            break;
          case 2:
            $.post("insertNewStimulus.php",{
                below:false,
                position:$(selectBox).parent().parent().index(),
                stim_set:set,
                group:selectBox.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.childNodes[0].childNodes[2].textContent
              }, function (received_data) {
                var data = JSON.parse(received_data)[0];
                stimuliData[$(selectBox).parent().parent().parent().parent().parent().parent().parent().parent().index()].stimuli.splice($(selectBox).parent().parent().index()-1,0,data);
                $(selectBox).parent().parent().before(createStimulusRow(data.stim_id,data.category1,data.category2,data.subcategory1,data.subcategory2,data.word,data.correct_response,data.instruction));
            });
            selectBox.selectedIndex = 0;
            break;
          case 3:
            $.post("insertNewStimulus.php",{
                below:true,
                position:$(selectBox).parent().parent().index(),
                stim_set:set,
                group:selectBox.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.childNodes[0].childNodes[2].textContent
              }, function (received_data) {
                var data = JSON.parse(received_data)[0];
                stimuliData[$(selectBox).parent().parent().parent().parent().parent().parent().parent().parent().index()].stimuli.splice($(selectBox).parent().parent().index(),0,data);
                $(selectBox).parent().parent().after(createStimulusRow(data.stim_id,data.category1,data.category2,data.subcategory1,data.subcategory2,data.word,data.correct_response,data.instruction));
            });
            selectBox.selectedIndex = 0;
            break;
          case 4:
            alert("copy");
            selectBox.selectedIndex = 0;
            break;
          default:
            selectBox.selectedIndex = 0;
        }
      }
      function remove_row (row) {
        var stim_id;
        if (row.childNodes[0].childNodes[0].alt) {
          stim_id = row.childNodes[0].childNodes[0].alt;
        } else {
          stim_id = row.childNodes[0].childNodes[0].textContent;
        }
        var poststr = "&stim_id=" + stim_id;
        if (window.XMLHttpRequest)
        {// code for IE7+, Firefox, Chrome, Opera, Safari
          xmlhttp=new XMLHttpRequest();
        }
        else
        {// code for IE6, IE5
          xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function (aEvt) {
          if (this.readyState == 4) {
            if(this.status !== 200) {
              location.href="servererror.php?status=" + this.status + "&statusText=" + encodeURIComponent(this.statusText);
            }
          }
        };
        xmlhttp.open("POST","removeStimulus.php",true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.send(poststr);
        stimuliData[$(row).parent().parent().parent().parent().parent().parent().index()].stimuli.splice($(row).index(),1);
        row.parentNode.removeChild(row);
      }
      function createStimulusTable (cat1,cat2,subcat1,subcat2,word,correct,instruction) {
        if (instruction == null || instruction == '') {
          var stimulusTable = document.createElement('table');
          var row0 = stimulusTable.insertRow(-1);
          var row1 = stimulusTable.insertRow(-1);
          var cat1Cell = row0.insertCell(0);
          cat1Cell.appendChild(document.createTextNode(cat1));
          var middleCell = row0.insertCell(1);
          middleCell.rowSpan = 2;
          middleCell.appendChild(document.createTextNode(word));
          row0.insertCell(2).appendChild(document.createTextNode(cat2));
          row1.insertCell(0).appendChild(document.createTextNode(subcat1));
          row1.insertCell(1).appendChild(document.createTextNode(subcat2));
          return stimulusTable;
        } else {
          return document.createTextNode(instruction);
        }
      }
      function changeStimulusType (row,type) {
        switch (type) {
          case 1: //IAT or Sequential Prime
            alert("changing to iat/sequential prime");
            break;
          case 2: //Instruction
            alert("changing to instruction");
            break;
        }
      }
      function addOptionsCell (table) {
        var row = table.rows[0];
        var cell = row.insertCell(-1);
        cell.rowSpan = "2";
        var iatButton = document.createElement('input');
        iatButton.onclick = function() {changeStimulusType(table,1)};
        iatButton.type = "radio";
        iatButton.name = "stimulusType";
        var instructionButton = document.createElement('input');
        instructionButton.onclick = function() {changeStimulusType(table,2)};
        instructionButton.type = "radio";
        instructionButton.name = "stimulusType";
        var maskingButton = document.createElement('input');
        maskingButton.type = "checkbox";
        maskingButton.name = "masking";
        maskingButton.checked = (stimuliData[$(row).parent().parent().index()].stimuli[$(row).index()].mask == "0") ? false : true;
        cell.appendChild(iatButton);
        cell.appendChild(document.createTextNode("IAT/Sequential Prime"));
        cell.appendChild(document.createElement('br'));
        cell.appendChild(instructionButton);
        cell.appendChild(document.createTextNode("Instruction"));
        cell.appendChild(document.createElement('br'));
        cell.appendChild(document.createElement('br'));
        cell.appendChild(maskingButton);
        cell.appendChild(document.createTextNode("Mask"));
      }
      function addStimulusRow (stim_id,cat1,cat2,subcat1,subcat2,word,correct,instruction) {
        var stimuliTable = document.getElementById('stimuliBody');
        stimuliTable.appendChild(createStimulusRow(stim_id,cat1,cat2,subcat1,subcat2,word,correct,instruction));
      }
      function addGroupRow (name) {
        var stimuliTable = document.getElementById('stimuliBody');
        var row = document.createElement('tr');
        var disclosureCell = document.createElement('td'); //TODO add disclosure functionality for groups
        var nameCell = document.createElement('td');
        nameCell.appendChild(document.createTextNode(name));
        row.appendChild(disclosureCell);
        row.appendChild(nameCell);
        stimuliTable.appendChild(row);
      }
      function insert_row(index) {
        alert("insert row at " + index);
      }
      function make_row_editable(evt) {
        //TODO disable all other edit buttons
        //TODO make this work for instruction rows. also. make it possible to switch.
        var stimulusTable = this.parentNode.parentNode.childNodes[1].childNodes[0];
        var row = 0;
        if (stimulusTable.rows) {
          while (row < stimulusTable.rows.length) {
            var cell = 0;
            while (cell < stimulusTable.rows[row].cells.length) {
              var text = stimulusTable.rows[row].cells[cell].childNodes[0].textContent;
              stimulusTable.rows[row].cells[cell].removeChild(stimulusTable.rows[row].cells[cell].childNodes[0]);
              var elem = document.createElement('input');
              elem.type = 'text';
              elem.value = text;
              stimulusTable.rows[row].cells[cell].appendChild(elem);
              cell++;
            }
            row++;
          }
        } else {
          var text = stimulusTable.textContent;
          var elem = document.createElement('input');
          elem.type = 'text';
          elem.value = text;
          stimulusTable.parentNode.appendChild(elem);
          stimulusTable.parentNode.removeChild(stimulusTable);
        }
        addOptionsCell(this.parentNode.parentNode.childNodes[1].childNodes[0]);
        this.onclick = make_row_uneditable;
        this.innerHTML = "Save";
      }
      function make_row_uneditable(evt) {
        //TODO reenable all edit buttons
        var stimuliRow = this.parentNode.parentNode;
        var loading = document.createElement('img');
        loading.alt = stimuliRow.childNodes[0].childNodes[0].textContent;
        stimuliRow.childNodes[0].removeChild(stimuliRow.childNodes[0].childNodes[0]);
        stimuliRow.childNodes[0].appendChild(loading);
        loading.src = "ajaxloader.gif";
        var stimulusTable = stimuliRow.childNodes[1].childNodes[0];
        var row = 0;
        if (stimulusTable.rows) {
          while (row < stimulusTable.rows.length) {
            var cell = 0;
            while (cell < stimulusTable.rows[row].cells.length) {
              stimulusTable.rows[row].cells[cell].childNodes[0].disabled = true;
              cell++;
            }
            row++;
          }
        } else {
          stimulusTable.disabled = true;
        }
        
        save_stimulus_row(stimuliRow);
      }
      function remove_all_stimuli() {
        $('#stimuliBody').replaceWith('<tbody id="stimuliBody"></tbody>');
      }
      function experiment_change() {
        remove_all_stimuli();
        var selectBox = document.getElementById("experiment_selector");
        set = selectBox.options[selectBox.selectedIndex].value;
        requestStimuliSet("set=" + set);
      }
    </script>
    <style type="text/css">
      .hidden {
        display: none;
      }
      table.center {
        width: 50%;
        height: 25%;
        font-family: "Helvetica"
      }
      td.categoryLeft {
        text-align: left;
        padding-left: 0%;
      }

      td.categoryRight {
        text-align: right;
        padding-right: 0%;
      }

      h1.categoryLeft {
        text-align: left;
      }

      h1.categoryRight {
        text-align: right;
      }

      h1.center {
        text-align: center;
      }
    </style>
  </head>
  <body onload="experiment_change();">
    <select id="experiment_selector" onchange="experiment_change();">
      <?php
        include 'connect.php';
        $query = "SELECT name,stimuli_set FROM experiments";
        $result = mysql_query($query);
        $num = mysql_num_rows($result);
        $i = 0;
        while ($i < $num) {
          $name = mysql_result($result, $i, "name");
          $set = mysql_result($result, $i, "stimuli_set");
          echo "<option value=\"$set\">$name</option>";
          $i++;
        }
        mysql_free_result($result);
        mysql_close();
      ?>
    </select> Responses: <span id="responseCount"></span>
    <div id="stimuliList">
      <table id="stimuliTable" style="border-width:2px; border-color:black;">
        <tbody id="stimuliBody"></tbody>
      </table>
    </div>
  </body>
</html>

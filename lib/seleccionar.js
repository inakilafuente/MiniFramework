function moveOver(Disp, Selec) {
    if (document.Form.elements[Disp].selectedIndex == -1)
        return false;
    var boxLength = document.Form.elements[Selec].length;
    var selectedItem = document.Form.elements[Disp].selectedIndex;
    var selectedText = document.Form.elements[Disp].options[selectedItem].text;
    var selectedValue = document.Form.elements[Disp].options[selectedItem].value;
    var i;
    var isNew = true;
    if (boxLength != 0) {
        for (i = 0; i < boxLength; i++) {
            thisitem = document.Form.elements[Selec].options[i].value;
            if (thisitem == selectedValue) {
                isNew = false;
                break;
            }
        }
    }
    if (isNew) {
        newoption = new Option(selectedText, selectedValue, false, false);
        document.Form.elements[Selec].options[boxLength] = newoption;
    }
    document.Form.elements[Disp].selectedIndex = -1;
} // Fin funcion

function moveAll(Disp, Selec) {
    var boxLength = document.Form.elements[Disp].length;
    var i;
    for (i = 0; i < boxLength; i++) {
        document.Form.elements[Disp].options[i].selected = true;
        moveOver(Disp, Selec);
    }
} // Fin funcion

function removeMe(Selec) {
    var boxLength = document.Form.elements[Selec].length;
    arrSelected = new Array();
    var count = 0;
    for (i = 0; i < boxLength; i++) {
        if (document.Form.elements[Selec].options[i].selected) {
            arrSelected[count] = document.Form.elements[Selec].options[i].value;
        }
        count++;
    }
    var x;
    for (i = 0; i < boxLength; i++) {
        for (x = 0; x < arrSelected.length; x++) {
            if (document.Form.elements[Selec].options[i].value == arrSelected[x]) {
                document.Form.elements[Selec].options[i] = null;
            }
        }
        boxLength = document.Form.elements[Selec].length;
    }
} // Fin funcion

function removeAll(Selec) {
    var boxLength = document.Form.elements[Selec].length;
    for (i = 0; i < boxLength; i++) {
        document.Form.elements[Selec].options[0] = null;
    }
} // Fin funcion


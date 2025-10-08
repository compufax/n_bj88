// Rutinas en JavaScript
 
	var digits = "0123456789"; 
	 
	var lowercaseLetters = "abcdefghijklmnopqrstuvwxyz" 
	 
	var uppercaseLetters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ" 
	 
	 
	// whitespace characters 
	var whitespace = " \t\n\r"; 
	 
	 
	// decimal point character differs by language and culture 
	var decimalPointDelimiter = "." 
	 
	 
	// non-digit characters which are allowed in phone numbers 
	var phoneNumberDelimiters = "()- "; 
	 

	function ir(reg) {
		document.forma.reg.value=reg;
		document.forma.cmd.value=1;
		document.forma.submit();
	}
	function cambiar(reg) {
		document.forma.reg.value=reg;
		document.forma.cmd.value=2;
		document.forma.submit();
	}
	function borrar(reg,cmdreferer) {
		res=confirm("Desea realmente eliminar el registro?");
		if (res) {
			document.forma.reg.value=reg;
			document.forma.cmd.value=3;
			document.forma.cmdreferer.value=cmdreferer;
			document.forma.submit();
		}
	}
	function par(reg,dat) {
		document.forma.reg.value=reg;
		document.forma.dat.value=dat;
		document.forma.cmd.value=4;
		document.forma.submit();
	}

	function bit(reg) {
		document.forma.reg.value=reg;
		document.forma.cmd.value=7;
		document.forma.submit();
	}
	
	function irOpcion(param1) {
		document.forma.cmd.value=param1;
		document.forma.submit();
	}
	
	function re(reg) {
	document.forma.reg.value=reg;
	}
	
	function atcr(action,target,cmd,reg) {
		document.forma.action=action;
		document.forma.target=target;
		document.forma.cmd.value=cmd;
		document.forma.reg.value=reg;
		document.forma.submit();
	}
	
	// Colorear renglones de colores 
	function sc(theRow, theCmd, theColor) {
		var c,theCells,rowCellsCnt,colorCell,newColor,colorOut,colorIn,colorClick,colorClick2;
		colorIn="#d1dadf";
		colorClick="#b5d0df";
		colorClick2="#7dbcdf";
		if (theColor==0) colorOut="#ffffff";
		if (theColor==1) colorOut="#d5d5d5";
		if (theColor==2) colorOut="#e5e5e5";
		theCells = theRow.cells;
		rowCellsCnt  = theCells.length;
		colorCell=theCells[0].getAttribute("bgcolor");
		if (colorCell==colorClick2) newColor=colorClick2; else newColor=colorClick;
		if (theCmd==1 && (colorCell!=colorClick && colorCell!=colorClick2)) newColor=colorIn;
		if (theCmd==0 && (colorCell!=colorClick && colorCell!=colorClick2)) newColor=colorOut;
		if (theCmd==2 && colorCell==colorClick) newColor=colorClick2;
		if (theCmd==2 && colorCell==colorClick2) newColor=colorIn;
		for (c = 0; c < rowCellsCnt; c++) {
			theCells[c].setAttribute("bgcolor", newColor, 0);
		}
	}
	
	//Objeto AJAX
	function crearObjeto() {

		try { objeto = new ActiveXObject("Msxml2.XMLHTTP");  }
		catch (e) {
			try { objeto = new ActiveXObject("Microsoft.XMLHTTP"); }
			catch (E) {
				objeto = false; 
			}
		}

		if (!objeto && typeof XMLHttpRequest!="undefined") {
			objeto = new XMLHttpRequest();
		}

		return objeto;

	}
	
	
	var popUpWin=0;
	function popUpWindow(URLStr, left, top, width, height)
	{
	  if(popUpWin)
	  {
		if(!popUpWin.closed) popUpWin.close();
	  }
	  popUpWin = open(URLStr, "popUpWin", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=yes,width="+width+",height="+height+",left="+left+", top="+top+",screenX="+left+",screenY="+top+"");
	}		

	// Dynamic Sort Table de Tom Delaringa, con adaptaciones de Eduardo Heredia eduardo.talentomx.com
	//global variable to store last column
	var lastSort = 0;
	function SortTable(sortColumn,type,tableID) {
		//get table, table body and all the rows
		var table =document.getElementById(tableID);
		var tbody =table.getElementsByTagName("tbody")[0];
		var rows =tbody.getElementsByTagName("tr");
		var rowArray = new Array();
		var length = rows.length;
		//clone each row for sorting
		for (var i=0; i<length; i++) {
		rowArray[i] = rows[i].cloneNode(true);
		}
		//if these match, row has been sorted = reverse array
		if (sortColumn == lastSort) {
			rowArray.reverse();
		} else {
			// set flag that column is sorted to reverse it later
			lastSort = sortColumn;
			type=type.toUpperCase();
			switch(type) {
				case "N":
				case "C":
				rowArray.sort(RowCompareNumbers);
				break;
				case "S":
				case "D":
				rowArray.sort(RowCompare);
				break;			
			}
		}
		
		var newTbody = document.createElement("tbody");
		var length = rowArray.length;
		for (var i=0; i<length; i++) {
			newTbody.appendChild(rowArray[i]);
		}
		
		table.replaceChild(newTbody, tbody);
		// sort Functions
		function RowCompare(a, b) {
			var aVal = a.getElementsByTagName("td")[lastSort].firstChild.nodeValue;
			var bVal = b.getElementsByTagName("td")[lastSort].firstChild.nodeValue;
			var rVal;
			// convertimos todo a minusculas para la comparacion.
			aVal=aVal.toLowerCase();
			//alert(aVal);
			//alert(bVal);
			bVal=bVal.toLowerCase();
			
			if(aVal == bVal){
				rVal = 0; 
			} else {
				if(aVal > bVal) {
					rVal = 1;
				} else {
					rVal = -1;
				}
			}
			return rVal;
		}

		function RowCompareNumbers(a, b) {
			var aVal = a.getElementsByTagName("td")[lastSort].firstChild.nodeValue;
			var bVal = b.getElementsByTagName("td")[lastSort].firstChild.nodeValue;
			// limpiamos  de , y $ si existieren
			aVal=aVal.replace(/,/g,"");
			bVal=bVal.replace(/,/g,"");
			aVal=aVal.replace(/\$/g,"");
			bVal=bVal.replace(/\$/g,"");
			//alert(aVal);
			//alert(bVal);
			// convertimos de string a numeros
			aVal = parseFloat(aVal);
			bVal = parseFloat(bVal);
			return (aVal - bVal);
		}
		
	}
	
	
	
	// Check whether string s is empty. 
 
	function isEmpty(s) 
	{   return ((s == null) || (s.length == 0)) 
	} 
	 
	 
	// si esta vacia la cadena 
	 
	function esNombre (s) 
	 
	{   var i; 
	    var cont; 
	    var aux; 
	   // alert(s); 
	    if (isEmpty(s)) return false; 
	    aux=s.length; 
			cont=0; 
	    for (i = 0; i < s.length; i++) 
	    {    
	        var c = s.charAt(i); 
					if (isLetter(c)||(c == " ")||(c == ".")||(c == "/"))  
					//if (isLetter(c)||(c == " "))  
	           { 
	             if (c == " ") 
					        { 
									  cont++;    
						      } 
						 } 
					else 
					   return false; 
				 if (cont==aux) return false; 
	    } 
	    return true; 
	} 
	
	
	// Returns true if character c is a digit  
	// (0 .. 9). 
	 
	function isDigit (c) 
	{   return ((c >= "0") && (c <= "9")) 
	} 
 
 	
	function isInteger (s) 
	 
	{   var i; 
	 
	    if (isEmpty(s))  
	       if (isInteger.arguments.length == 1) return defaultEmptyOK; 
	       else return (isInteger.arguments[1] == true); 
	 
	    // Search through string's characters one by one 
	    // until we find a non-numeric character. 
	    // When we do, return false; if we don't, return true. 
	 
	    for (i = 0; i < s.length; i++) 
	    {    
	        // Check that current character is number. 
	        var c = s.charAt(i); 
	 
	        if (!isDigit(c)) return false; 
	    } 
	 
	    // All characters are numbers. 
	    return true; 
	} 	

	
	function isIntegerInRange (s, a, b) 
	{   if (isEmpty(s))  
	       if (isIntegerInRange.arguments.length == 1) return defaultEmptyOK; 
	       else return (isIntegerInRange.arguments[1] == true); 
	 
	    // Catch non-integer strings to avoid creating a NaN below, 
	    // which isn't available on JavaScript 1.0 for Windows. 
	    if (!isInteger(s, false)) return false; 
	 
	    // Now, explicitly change the type to integer via parseInt 
	    // so that the comparison code below will work both on  
	    // JavaScript 1.2 (which typechecks in equality comparisons) 
	    // and JavaScript 1.1 and before (which doesn't). 
	    var num = parseInt (s); 
	    return ((num >= a) && (num <= b)); 
	} 	
	

		
	function isFloat (s) 
 
	{   var i; 
	    var seenDecimalPoint = false; 
	 
	    if (isEmpty(s))  
	       if (isFloat.arguments.length == 1) return defaultEmptyOK; 
	       else return (isFloat.arguments[1] == true); 
	 
	    if (s == decimalPointDelimiter) return false; 
	 
	    // Search through string's characters one by one 
	    // until we find a non-numeric character. 
	    // When we do, return false; if we don't, return true. 
	 
	    for (i = 0; i < s.length; i++) 
	    {    
	        // Check that current character is number. 
	        var c = s.charAt(i); 
	 
	        if ((c == decimalPointDelimiter) && !seenDecimalPoint) seenDecimalPoint = true; 
	        else if (!isDigit(c)) return false; 
	    } 
	 
	    // All characters are numbers. 
	    return true; 
	} 
	
	function formatCurrency(num) {
		num = num.toString().replace(/$|,/g,'');
		if(isNaN(num))
		num = "0";
		sign = (num == (num = Math.abs(num)));
		num = Math.floor(num*100+0.50000000001);
		cents = num%100;
		num = Math.floor(num/100).toString();
		if(cents<10)
		cents = "0" + cents;
		for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++)
		num = num.substring(0,num.length-(4*i+3))+','+
		num.substring(num.length-(4*i+3));
		return (((sign)?'':'-') + num + '.' + cents);
	}	
	
	function validaCampo(num,id) {
		if(!isFloat(num)) {
			window.alert("Solo numeros"); 
			document.getElementById(id).value=""; 
		} 
	
	}	
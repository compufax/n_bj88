<?php
define('PARAGRAPH_STRING', '~~~');
require_once("class.multicelltag.php");

$pdf = new FPDF_MULTICELLTAG();
$pdf->SetMargins(30,15,25);

$pdf->AddPage();

$pdf->SetFont('arial','',11);
$pdf->SetTextColor(200,10,10);
$pdf->SetFillColor(255,252,232);

$pdf->SetStyle("p","times","",11,"130,0,30");
$pdf->SetStyle("pb","times","B",11,"130,0,30");
$pdf->SetStyle("t1","arial","",11,"254,252,222");
$pdf->SetStyle("t1","arial","",11,"0,151,200");
$pdf->SetStyle("t2","arial","",11,"0,151,200");
$pdf->SetStyle("t3","times","B",14,"203,0,48");
$pdf->SetStyle("t4","arial","BI",11,"0,151,200");
$pdf->SetStyle("hh","times","B",11,"255,189,12");
$pdf->SetStyle("ss","arial","",7,"203,0,48");
$pdf->SetStyle("font","helvetica","",10,"0,0,255");
$pdf->SetStyle("style","helvetica","BI",10,"0,0,220");
$pdf->SetStyle("size","times","BI",13,"0,0,120");
$pdf->SetStyle("color","times","BI",13,"0,255,255");

$txt1 = "Done by <t1 href=\"mailto:klodoma@ar-sd.net'>Bintintan Andrei</t1>";
$txt2 = "<p>
<t3>Description</t3>

\tThis method allows printing <t4><TAG></t4> formatted text with line breaks. They can be automatic (as soon as the text reaches the right border of the cell) or explicit (via the <pb>\\n</pb> character). As many cells as necessary are output, one below the other.
Text can be <hh>aligned</hh>, <hh>cente~~~red</hh> or <hh>justified</hh>. Different <font>Font</font>, <size>Sizes</size>, <style>Styles</style>, <color>Colors</color> can be used. The cell block can be framed and the background painted. The behavior/paramaters of the method are the same as to the <t2 href='http://www.fpdf.org/en/doc/multicell.htm'>FPDF Multicell method</t2>. <style href='www.fpdf.org'>Links</style> can be used in any tag.
\t<t4>TAB</t4> spaces (<pb>\\t</pb>) can be used. The <t4>ttags</t4> tag name is reserved for the TAB SPACES.
\tVariable Y relative positions can be used for <ss ypos='-0.8'>Subscript</ss> or <ss ypos='1.1'>Superscript</ss>.</p>
<style>
\t<hh size='50' >Controlled Tab Space~~~</hh> - Tab Space 1
\t<hh size='60' > ~~~</hh><font> - Tab Space 2</font>
\t<hh size='60' > ~~~</hh> - Tab Space 2
\t<hh size='70' > ~~~</hh><hh> - Tab Space 3</hh>
\t<hh size='50' > ~~~</hh> - Tab Space 1
\t<hh size='60' > ~~~</hh><t4> - Tab Space 2</t4>
</style>
\tIf no <t4><TAG></t4> is specified then the FPDF current settings are used.\n\n";
$txt2_1 = "<p>
\t<size size='50'>Paragraphs can be used:~~~- Paragraph 1</size>
\t<style size='50'>50 units~~~- Paragraph 1</style>
\t<style size='60'>60 units~~~</style>- Long Long Text Within Paragraph!!! Don't USE JUSTIFIED ALIGN IN THIS CASE
\t<t4 size='60'>60 units~~~- Paragraph 2</t4></p>
";
$txt3 ="<style>Best Regards</style>";


$pdf->MultiCellTag(0, 5, $txt1, 0, "L", 0); $pdf->Ln(10);
$pdf->MultiCellTag(0, 5, $txt2, 1, "J", 1); $pdf->Ln(10);
$pdf->MultiCellTag(0, 5, $txt2_1, 1, "L", 1); $pdf->Ln(10);
$pdf->MultiCellTag(0, 5, $txt3, 0, "R", 0); $pdf->Ln(1);

$pdf->Output();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.pedrovalente.com/projetos/falec.rss">
<title>Falec 1.1</title>
</head>

<body>
<a href="http://www.pedrovalente.com/projetos/falec.rss">Lista em RSS</a><br>
<?php 

setlocale(LC_ALL, "pt_BR");

function stripfromtext($haystack, $bfstarttext, $endsection) {
  $startpostext = $bfstarttext;
  $startposlen = strlen($startpostext);
  $startpos = strpos($haystack, $startpostext);
  $endpostext = $endsection;
  $endposlen = strlen($endpostext);
  $endpos = strpos($haystack, $endpostext, $startpos);
  return substr($haystack, $startpos + $startposlen, $endpos - ($startpos + $startposlen));
}

function stripfromtextarray($haystack, $bfstarttext, $endsection, $myarray=array(), $offset=0) {
  $startpostext = $bfstarttext;
  $startposlen = strlen($startpostext);
  $startpos = strpos($haystack, $startpostext, $offset);
  $endpostext = $endsection;
  $endposlen = strlen($endpostext);
  $endpos = strpos($haystack, $endpostext, $startpos);
  $myarray[] = substr($haystack, $startpos + $startposlen, $endpos - ($startpos + $startposlen));
  $offset = $endpos;
  if (is_numeric(strpos($haystack, $startpostext, $offset))) {
	
   return stripfromtextarray($haystack,$startpostext, $endpostext, $myarray, $offset);
  }
  else {
   return $myarray;
  }
}

function RemoveShouting($string)
{
 $lower_exceptions = array(
       "do" => "1", "da" => "1", "dos" => "1", "das" => "1", "de" => "1", "falecida" => "1", "falecido" => "1",
	   "da cidade de origem" => "1"
 );
                                    
 $higher_exceptions = array(
       "I" => "1", "II" => "1", "III" => "1", "IV" => "1",
       "V" => "1", "VI" => "1", "VII" => "1", "VIII" => "1",
       "XI" => "1", "X" => "1"
 );

 $words = split(" ", $string);
 $newwords = array();
 
 foreach ($words as $word)
 {
       if (!$higher_exceptions[$word])
               $word = strtolower($word);
       if (!$lower_exceptions[$word])
               $word = ucfirst($word);
         array_push($newwords, $word);
 
 }
      
 return join(" ", $newwords); 
}


$ch = curl_init();
curl_setopt ($ch, CURLOPT_URL, 'http://obituarios.curitiba.pr.gov.br/mase/obituarios.asp');
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 1);
$lista = curl_exec($ch);
curl_close($ch);

$lista = substr($lista, strpos($lista,"<body>")+6, strpos($lista, "</body>")-7);
$lista = str_replace("	", "", $lista);

//echo $lista;

$fichaarray = stripfromtextarray($lista, "<TABLE cellpadding='0' cellspacing='0' border='0' style='padding-left:20px;' width='100%'>", "</TABLE>");

for ($i=0; $i<count($fichaarray); $i++) {

$nome[$i] = stripfromtext ($fichaarray[$i], "<TD width='40%'>Nome:</TD><TD>", "</TD></TR>");

$idade[$i] = stripfromtext ($fichaarray[$i], "<TD>Idade:</TD><TD>", "</TD></TR>");
$idade[$i] = strtolower($idade[$i]);


if (stristr($idade[$i],"DIA") != "") {
	$idade[$i] = "recém-nascid";	
}


$profissao[$i] = stripfromtext ($fichaarray[$i], "<TD>Profissão:</TD><TD>", "</TD></TR>");


$viuva[$i] = str_replace("me:</TD><TD>", "", stripfromtext($fichaarray[$i], "<TD>Viúvo(a):</TD><TD>", "</TD></TR>"));


$diaehora[$i] = stripfromtext ($fichaarray[$i], "<TD>Data e Horário de Sepultamento:&nbsp;</TD><TD>", "</TD></TR>");

$local[$i] = stripfromtext ($fichaarray[$i], "<TD>Local de Sepultamento:</TD><TD>", "</TD></TR>");
$local[$i] = rtrim($local[$i]);

$sexoF = 0;
$sexoV = 0;

if (strpos($viuva[$i], "FALECIDO") != "") {
	$sexoF--;
	$sexoV++;
} else if (strpos($viuva[$i], "FALECIDA") != "") {
	$sexoF++;
	$sexoV--;
} else {
	if (substr($nome[$i], (strpos($nome[$i], " ")-1), 1) == "A") {
		$sexoF--;
		$sexoV++;	
	} else if (substr($nome[$i], (strpos($nome[$i], " ")-1), 1) == "O") {
		$sexoF++;
		$sexoV--;
	}
	if ($viuva[$i] != $nome[$i]) {
		if (substr($viuva[$i], (strpos($viuva[$i], " ")-1), 1) == "O") {
			$sexoF--;
			$sexoV++;	
		} else if (substr($viuva[$i], (strpos($viuva[$i], " ")-1), 1) == "A") {
			$sexoF++;
			$sexoV--;
		}
	
	}
}
if ($sexoF > $sexoV) {
	$profissao[$i] = str_replace("(A)", "", $profissao[$i]);
	$txtViuva = "viúva";
	$fim_da_palavra = "o";

} else if ($sexoF < $sexoV) {
	$profissao[$i] = str_replace("O(", "(", $profissao[$i]);
	$profissao[$i] = str_replace("(A)", "A", $profissao[$i]);
	$txtViuva = "viúvo";
	$fim_da_palavra = "a";
} else {
	$txtViuva = "viúva(o)";
	$fim_da_palavra = "o";
}
if ($idade[$i] == "recém-nascid") {
	$idade[$i] += $fim_da_palavra;
}

$strFicha = "Ficha ".$i."<br>";
$strFicha .= RemoveShouting($nome[$i]);

if (substr($idade[$i],0,2) == "1 ") {
	$strFicha .= ", ". str_replace("(s)","",$idade[$i]);
} else {
	$idade[$i] = str_replace("(","",$idade[$i]);
	$strFicha .= ", ". str_replace(")","",$idade[$i]);
};

if ($profissao[$i] != "OUTROS" && $profissao[$i] != substr($nome[$i],1)) {
	$strFicha .= ", ". strtolower($profissao[$i]);
}
$strFicha .= ".";

if (strtolower($viuva[$i]) != strtolower($nome[$i]) && stristr($viuva[$i],"FALECID") == "") {
	$strFicha .= " Deixa ".$txtViuva." ". RemoveShouting($viuva[$i]).".";
}

$diaehora[$i] = strtolower($diaehora[$i]);
$diaehora[$i] = str_replace(" h", "", $diaehora[$i]);
$diaehora[$i] = str_replace(":", "h", $diaehora[$i]);
$dia = stripfromtext ($diaehora[$i], ", ", " de");
$amanha  = date("j", mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
$ontem = date("j", mktime(0, 0, 0, date("m"), date("d")-1,  date("Y")));
$hoje = date("j");
switch ($dia) {
	case $hoje:
		//É o dia em relação ao jornal do dia seguinte
		$strData = "ontem";
	break;
	case $amanha:
		//O dia em que o jornal vai sair.
		$strData = "hoje";
	break;
	//case $ontem:
	//	$strData = "ontem";
	//break;
	default:
		$strData = substr($diaehora[$i], 0, (strpos($diaehora[$i], " de 200")) );
}
$temHora= strpos($diaehora[$i], "às ");
$strFicha .= " Sepultamento ";
if ($temHora == "" || $strData == "ontem") {
	$strFicha .= $strData;
} else {
	$strFicha .= $strData. ", ". substr($diaehora[$i], strpos($diaehora[$i], "às "));
}

if ($local[$i] == "OUTROS") {
	$strFicha .= ", em cemitério a ser designado";
} else {
	$strFicha .= ", no Cemitério ". RemoveShouting($local[$i]);
}
$strFicha .= ".<br><br>";

echo $strFicha;
};




//echo $viuva[3];

?>
</body>
</html>

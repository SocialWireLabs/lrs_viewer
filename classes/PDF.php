<?php

$path = elgg_get_plugins_path();

require($path . 'lrs_viewer/vendor/fpdf/fpdf.php');


class PDF extends SW_FPDF
{

   var $header;

   function __construct($title = ''){
      parent::__construct();
      $this->header = $this->UTF8toUTF16($title);
   }


   //Cabecera de página
   function Header()
   {
      $path = elgg_get_plugins_path();
   		//logo
      $this->Image($path. 'lrs_viewer/graphics/logo.jpg',10,8,33);

      $this->SetFont('Arial','B',15);
      	//desplazarse a la derecha
      //$this->Cell(80);
      	//titulo
      $this->Cell(210,20,$this->header,0,0,'C');
      	//salto de linea
      $this->Ln(30);
   }

   function Footer()
	{

	$this->SetY(-10);

	$this->SetFont('Arial','I',8);

	$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
	   }

   function UTF8toUTF16($s)
   {
   // Convert UTF-8 to UTF-16BE with BOM
   $res = "";
   $nb = strlen($s);
   $i = 0;
   while($i<$nb)
   {
      $c1 = ord($s[$i++]);
      if($c1>=224)
      {
         // 3-byte character
         $c2 = ord($s[$i++]);
         $c3 = ord($s[$i++]);
         $res .= chr((($c1 & 0x0F)<<4) + (($c2 & 0x3C)>>2));
         $res .= chr((($c2 & 0x03)<<6) + ($c3 & 0x3F));
      }
      elseif($c1>=192)
      {
         // 2-byte character
         $c2 = ord($s[$i++]);
         $res .= chr(($c1 & 0x1C)>>2);
         $res .= chr((($c1 & 0x03)<<6) + ($c2 & 0x3F));
      }
      else
      {
         // Single-byte character
         $res .= "\0".chr($c1);
      }
   }
   return $res;
   }
}
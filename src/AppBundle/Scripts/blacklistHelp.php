<?php

if($_POST['j']!='jlo'){
	echo "no permission";
	die();
}

$arrDb1=array('leaduni');//lead uni o comunqu con campi tipo cellulare, email
$arrDb2=array();//leadouto o comunqu con campi tipo PHONE1, EMAIL
$emails=array('TEST');
$cells=array('33');
echo "LEADUNI<br><br>";
foreach($arrDb1 as $tb){
	$query="SELECT * FROM ".$tb." WHERE ";
	$count=0;
	foreach($emails as $email){
		if(count==0){
			$query.="email = '".$email."'";
		}else{
			$query.="AND email = '".$email."'";
		}
	}
	echo $query."<br>";
	
	$query="SELECT * FROM ".$tb." WHERE ";
	$count=0;
	foreach($cells as $cell){
		if(count==0){
			$query.="cellulare = '".$cell."'";
		}else{
			$query.="AND cellulare = '".$cell."'";
		}
	}
	echo $query."<br>";
}

echo "LEADOUT<br><br>";
foreach($arrDb1 as $tb){
	$query="SELECT * FROM ".$tb." WHERE ";
	$count=0;
	foreach($emails as $email){
		if(count==0){
			$query.="EMAIL = '".$email."'";
		}else{
			$query.="AND EMAIL = '".$email."'";
		}
	}
	echo $query."<br>";
	
	$query="SELECT * FROM ".$tb." WHERE ";
	$count=0;
	foreach($cells as $cell){
		if(count==0){
			$query.="PHONE1 = '".$cell."'";
		}else{
			$query.="AND PHONE1 = '".$cell."'";
		}
	}
	echo $query."<br>";
}


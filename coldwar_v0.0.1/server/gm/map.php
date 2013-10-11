<?php
	include('../lib/debug.php');
	include('../lib/graph.php');

	/*
	class Borough extends Vertex{
		public $name;
	}
	
	class Airport extends Edge{
		public $name;
	}
	
	class Metro extends Edge{
		
	}
	
	class City extends Graph{
		public $name;
	}
	*/
	
	$G0 = new Graph('map A');
	$G1 = new Graph('map B');

	$G0->addVertices(array(
		$vxA = new Vertex('A'),
		$vxB = new Vertex('B'),
		$vxC = new Vertex('C'),
		$vxD = new Vertex('D')
	));
	
	
	$G1->addVertices(array(
		$vxE = new Vertex('E'),
		$vxF = new Vertex('F'),
		$vxG = new Vertex('G'),
		$vxH = new Vertex('H')
	));
	
	
	$G0->addArcs(array(
		$arcA = new Arc($vxA, $vxB, 10),
		$arcB = new Arc($vxB, $vxC, 8),
		$arcC = new Edge($vxC, $vxD, 1),
		$arcD = new Edge($vxC, $vxA, 2),
		$arcE = new Edge($vxD, $vxE, 4)
	));
	
	
	$G1->addArcs(array(	
		$arcF = new Arc($vxE, $vxF, 5),
		$arcG = new Arc($vxE, $vxG, 9),   
		$arcH = new Arc($vxE, $vxH, 7),	   
		$arcI = new Arc($vxH, $vxG, 4),
		$arcJ = new Arc($vxG, $vxF, 3)			
	));
	
	
	printr($G0->walk($vxA, $vxD));
	
	
	
	
	/*
	$east_berlin_URSS = array(
		'Friedrichshain',
		'Hellersdorf',
		'HohenschönhausenKöpenick',
		'Lichtenberg',
		'MarzahnMitte',
		'Pankow',
		'Prenzlauer Berg',
		'Treptow',
		'Weißensee'
	);

	$west_berlin_US = array(
		'Neukölln',
		'Kreuzberg',
		'Schöneberg',
		'Steglitz',
		'Tempelhof',
		'Zehlendorf',
	);

	$west_berlin_UK = array(
		'Charlottenburg',
    	'Tiergarten',
    	'Wilmersdorf',
    	'Spandau'
	);

	$west_berlin_FR = array(
		'Reinickendorf',
		'Wedding'
	);
	*/

?>

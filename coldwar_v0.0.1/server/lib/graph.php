<?php
    include ('./debug.php');

    class Arc{
        
        public  $id;
        public  $endpoints;
        public  $weight;
        public  $data;

        public function Arc(Vertex $vxA, Vertex $vxB, $weight = 1, $data = null){
            $this->endpoints    = array($vxA, $vxB);
            $this->weight       = $weight;
            $this->data         = $data;
        }

    } // ARC .END OF BLOCK //

    class Edge extends Arc{

        public function Edge(Vertex $from, Vertex $to, $weight = 1, $data = null){
            parent::__construct($from, $to, $weight, $data);
        }

    } // EDGE .END OF BLOCK //

    class Vertex{

        public  $id;
		public	$sign = false;
        private $data;
		
		public function Vertex($data = null){
			$this->data = $data;
		}

        public function id($id = null){
            if($id === null)
                return $this->id;
            
            $this->id = $id; 
        }

		// GETTERS & SETTERS
        public function setData($data){
            $this->data = $data;   
        }

        public function getData(){
            return $this->data;    
        }
		// GETTERS & SETTERS .END OF BLOCK
		
    } // VERTEX .END OF BLOCK //

    // PATH //
    class Path{
        public      $id;
        public      $path = array();
		protected	$graph;
		protected   $from;
        protected   $to;
        protected   $cost = 0;
		
		public function Path(Graph $graph, array $arcs = null){
			$this->graph = $graph;
			if($arcs !== null)
				$this->path = $arcs;
		}
       
        public function addArc(Arc $arc){
            $this->path[] = $arc;
            $this->cost += $arc->weight;
        }

        public function addArcs(array $arcs){
            foreach($arcs as $arc)
                $this->addArc($arc);
        }

        public function getPathEnd(){
            $last = end($this->path);
            return $this->path[$last];
        }

        public function getPathBegin(){
            reset($this->path);
            return current($this->path);
        }

        public function walk(){

            $prev = $this->getPathBegin();
                        
            foreach($this->path as $vx){                  
                
				if(!$this->graph->hasLinkFrom($vx, $prev))
                    return array($prev, $vx);
               
				$prev = $vx;    
            }

            return true;
        }

    } // PATH .END OF BLOCK //
		
    class Graph{
        protected static $vxcnt     = 0;
        protected static $arcnt     = 0;
        protected $id;
        protected $vertices         = array();
        protected $arcs             = array();
        protected $adjacencyList    = array();
        protected $incidencyMatrix  = array(array());
        
		// ROUTE ALGORITHM
		private $walks				= array();
        
        public function Graph($id){
            $this->id = $id;
        }
		
		// GETTERS & SETTERS //
		
        public function id(){
            return $this->id;    
        }
        
        // ARCS
        public function addArc(Arc $arc){
            
            if(isset($arc->id) && in_array($arc->id, array_keys($this->arcs)))
                return false;

            $arc->id = Graph::$arcnt++;
            $this->arcs[$arc->id] = $arc;
            $this->adjacencyList[$arc->endpoints[0]->id][] = $arc->endpoints[1]->id;
            $this->adjacencyList[$arc->endpoints[1]->id][] = $arc->endpoints[0]->id;
            
            $this->incidencyMatrix[$arc->endpoints[0]->id][$arc->endpoints[1]->id] = $arc->weight;
            
            if($arc instanceOf Edge)
                $this->incidencyMatrix[$arc->endpoints[1]->id][$arc->endpoints[0]->id] = $arc->weight;    
            
            return true;
        }

        public function addArcs(array $arcs){

            foreach($arcs as $arc)
                $this->addArc($arc);

            return true;
        }

        // VERTICES
        public function addVertex(Vertex $vx){
                      
            if(isset($vx->id) && in_array($vx->id, array_keys($this->vertices)))
                return false;

            $vx->id = Graph::$vxcnt++;
            $this->vertices[$vx->id] = $vx;
            $this->adjacencyList[$vx->id] = array();
			$this->incidencyMatrix[$vx->id][$vx->id] = 0;
            return true;
        }
        
        public function addVertices(array $vxs){

            foreach($vxs as $vx){
                $this->addVertex($vx);
            }
            return true;
        }

        public function getVertices(){
            return $this->vertices;    
        }

        public function getAdjacencyList(){
            return $this->adjacencyList;    
        }

        public function getIncidencyMatrix(){
            return $this->incidencyMatrix;
        }
        // GETTERS & SETTERS .END OF BLOCK //

        // NEIGHBORS
	
        public function getIncomingNeighbors(Vertex $vx){
            if(!in_array($vx->id, array_keys($this->vertices)))
                return false;
            
            $incoming = array();
            
            foreach($this->incidencyMatrix as $id => $vertex){
				if($id == $vx->id)
					continue;
					
                if(isset($vertex[$vx->id]))
                    $incoming[$id] = $this->vertices[$id];
            }                
            
            return $incoming;
        }
		
		public function getIncomingWeights(Vertex $vx){
            if(!in_array($vx->id, array_keys($this->vertices)))
                return false;
            
            $incoming = array();
            
            foreach($this->incidencyMatrix as $id => $vertex){					
                if(isset($vertex[$vx->id]))
                    $incoming[$id] = $this->incidencyMatrix[$id][$vx->id];
            }                
            
            return $incoming;
        }
		
		public function getOutcomingWeights(Vertex $vx){
			if(!in_array($vx->id, array_keys($this->vertices)))
				return false;
			
			$outcoming = array();
            
            foreach($this->incidencyMatrix[$vx->id] as $id => $weight)
                $outcoming[$id] = $weight;
            
            return $outcoming;
			
		}

        public function getOutcomingNeighbors(Vertex $vx){
            if(!in_array($vx->id, array_keys($this->vertices)))
                return false;
            
            $outcoming = array();
            
            foreach($this->incidencyMatrix[$vx->id] as $id => $weight){
				if($id == $vx->id)
					continue;
					
				$outcoming[$id] = $this->vertices[$id];
			}
            
            return $outcoming;
        }
		
		public function hasLinkTo(Vertex $vxA, Vertex $vxB){
			return isset($this->incidencyMatrix[$vxA->id][$vxB->id]);
		}
		
		public function hasLinkFrom(Vertex $vxA, Vertex $vxB){
			return isset($this->incidencyMatrix[$vxB->id][$vxA->id]);
		}

        // PATHS

        protected function searchBfirst(Vertex $from, Vertex $to, $path = array()){
		
			/*
			$this->queue[] = $from;
			$this->walks = array_fill_keys(array_keys($this->vertices), 0);
			*/
			
			$neighbors = $this->getOutcomingNeighbors($from);
			$path[] = $id;
			
			
			foreach($neighbors as $id => $weight){	
				if(!isset($this->queue[$id]))
					continue;
					
			}
			
			
        }			

        public function walk(Vertex $from, Vertex $to){
           
			$queue[] = $from;
			$walks = array_fill_keys(array_keys($this->vertices), array('cost' => -1, 'from' => null));
			$walks[$from->id]['cost'] = 0;
				
			while(count($queue) > 0){
				$vx			= array_shift($queue);
				$vx->sign	= true;			
				
				$neighbors	= $this->getOutcomingNeighbors($vx);
					
				foreach($neighbors as $neighbor){
					if(!$neighbor->sign){
						$queue[] = $neighbor;		
				
						if(($walks[$neighbor->id]['cost'] < 0) || ($this->incidencyMatrix[$vx->id][$neighbor->id] < $walks[$neighbor->id]['cost'])){
							$walks[$neighbor->id]['from'] = $vx->id;
							$walks[$neighbor->id]['cost'] = $this->incidencyMatrix[$vx->id][$neighbor->id];
						}
							
						/*
						echo 'ID: '.$neighbor->id.'<br/>';
						echo 'CURRENT COST: '.($walks[$neighbor->id]['cost']).'<br/>';
						echo 'CURRENT PARENT: '.($walks[$neighbor->id]['from']).'<br/>';
						echo 'COST FROM '.$vx->id.' VERTEX: '.($this->incidencyMatrix[$vx->id][$neighbor->id]);
						echo '<hr/>';
						*/
					}
				}	
			}
			
			printr($walks);
			
			
			$i = $to->id;
			
			while($i != $from->id){
				$path[] = $this->vertices[$i]->getData();
				$i = $walks[$i]['from'];
			}	
			
			$path[] = $from->getData();
			
			return array_reverse($path);
			
        }
    }

/*-------------------------------------------------
    INIT
-------------------------------------------------*/

$G = new Graph('map');

$G->addVertices(array(
    $vxA = new Vertex('A'),
    $vxB = new Vertex('B'),
    $vxC = new Vertex('C'),
    $vxD = new Vertex('D'),
	$vxE = new Vertex('E'),
    $vxF = new Vertex('F'),
    $vxG = new Vertex('G'),
    $vxH = new Vertex('H')
));

$G->addArcs(array(
    $arcA = new Edge($vxA, $vxB, 10),
	$arcC = new Edge($vxA, $vxC, 8),
	$arcI = new Arc($vxA, $vxH, 1),
	$arcI = new Arc($vxH, $vxE, 2),
	$arcE = new Arc($vxE, $vxC, 4),
	$arcF = new Arc($vxC, $vxF, 5),
    $arcB = new Arc($vxB, $vxC, 9),   
    $arcD = new Arc($vxD, $vxC, 7),	   
    $arcG = new Arc($vxC, $vxG, 4),
    $arcH = new Arc($vxG, $vxD, 3)			
));

$P = new Path($G, array(
    $vxD,
    $vxC,
    $vxA,
    $vxC,
    $vxB
));


echo 'ADJACENCY LIST';
printr($G->getAdjacencyList());
echo '<hr/>';
echo 'INCIDENCY MATRIX';
printr($G->getIncidencyMatrix());
echo '<hr/>';
echo 'INCOMING NEIGHBORS';
printr($G->getIncomingNeighbors($vxA));
echo '<hr/>';
echo 'OUTCOMING NEIGHBORS';
printr($G->getOutcomingNeighbors($vxA));
echo '<hr/>';

/*
echo 'PATH';
printr($P);
echo '<hr/>';
echo 'WALK';
printr($P->walk());
*/

printr($G->walk($vxA, $vxF));

?>

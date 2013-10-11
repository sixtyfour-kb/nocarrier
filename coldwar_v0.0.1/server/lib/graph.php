<?php
	// ARC //
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

	// EDGE //
    class Edge extends Arc{

        public function Edge(Vertex $from, Vertex $to, $weight = 1, $data = null){
            parent::__construct($from, $to, $weight, $data);
        }

    } // EDGE .END OF BLOCK //

    class Vertex{

        public 		$id;
		public		$sign = false;
		//protected	$graph;
        protected	$data;
		
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
		
		public function setGraph(Graph $graph){
			//$this->graph = $graph;
		}
		
		public function getGraph(){
			//return $this->graph;
		}
		// GETTERS & SETTERS .END OF BLOCK
		
    } // VERTEX .END OF BLOCK //

	// GRAPH //
    class Graph{
        protected static $vxcnt     = 0;
        protected static $arcnt     = 0;
        protected $id;
        protected $vertices         = array();
        protected $arcs             = array();
        protected $adjacencyList    = array();
        protected $incidencyMatrix  = array(array());
		protected $walks			= array(array());
            
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
			$vx->setGraph($this);
			
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
				if($id == $vx->id)
					continue;
					
                if(isset($vertex[$vx->id]))
                    $incoming[$id] = $this->incidencyMatrix[$id][$vx->id];
            }                
            
            return $incoming;
        }
		
		public function getOutcomingWeights(Vertex $vx){
			if(!in_array($vx->id, array_keys($this->vertices)))
				return false;
			
			$outcoming = array();
            
            foreach($this->incidencyMatrix[$vx->id] as $id => $weight){
				if($id == $vx->id)
					continue;
					
                $outcoming[$id] = $weight;
            }
			
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
		// NEIGHBORS .END OF BLOCK //
		
		public function hasLinkTo(Vertex $vxA, Vertex $vxB){
			return isset($this->incidencyMatrix[$vxA->id][$vxB->id]);
		}
		
		public function hasLinkFrom(Vertex $vxA, Vertex $vxB){
			return isset($this->incidencyMatrix[$vxB->id][$vxA->id]);
		}
		
		public function walk($from, $to){
			
			if(isset($this->walks[$from->id]) && count($this->walks[$from->id]) > 0)
				$walks = $this->walks;				
			else
				$walks = $this->walkBreadthFirst($from);
			
			if(count($walks) < 1)
				return false;
		
			$i = $to->id;
			
			while($walks[$i] !== null){
				$path[] = $this->vertices[$i]->getData();
				$i = $walks[$i]['from'];
			}	
				
			return array_reverse($path);
		}

        // ROUTING ALGORITHMS
        protected function walkBreadthFirst(Vertex $from){
           	
			$queue[] = $from;
			$walks = array_fill_keys(array_keys($this->vertices), array('cost' => -1, 'from' => null));
			$walks[$from->id]['cost'] = 0;
			
			$cost = 0;
				
			while(count($queue) > 0){
				$vx = array_shift($queue);
					
				if($vx->sign === true)
					continue;
				
				$neighbors	= $this->getOutcomingNeighbors($vx);
			
				foreach($neighbors as $neighbor){
					
					$queue[] = $neighbor;	

					if( ( $walks[$neighbor->id]['cost'] < 0 ) || ( $walks[$neighbor->id]['cost'] > ( $this->incidencyMatrix[$vx->id][$neighbor->id] + $cost ) ) ){
					
						$cost += $this->incidencyMatrix[$vx->id][$neighbor->id];						
						$walks[$neighbor->id]['from'] = $vx->id;
						$walks[$neighbor->id]['cost'] = $cost;
						
					}					
				}				
				$vx->sign = true;	
			}
			$this->walks[$from->id] = $walks;
			$this->unsignVertices();			
			return $walks;			
		}
		
		private function unsignVertices(){
			foreach($this->vertices as $vx)
				$vx->sign = false;
		}
    } // GRAPH .END OF BLOCK //
?>

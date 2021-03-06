<?php

	require '../config.php';

	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/societe/class/societe.class.php');

	
	$get=GETPOST('get');
	$put=GETPOST('put');
	
	switch ($get) {
		case 'propals':
		
			__out(_propasals((int)GETPOST('min'),(int)GETPOST('max'),(int)GETPOST('start'),(int)GETPOST('end'),GETPOST('special'),GETPOST('fk_user')), 'json');
						
			break;
		default:
			
			break;
	}
	
	switch ($put) {
		case 'propal':
		
			_update_proba_propal((int)GETPOST('propalid'), GETPOST('proba'), GETPOST('end'), GETPOST('special'));
						
			break;
		default:
			
			break;
	}
	
function _update_proba_propal($fk_propal, $proba,$nb_month, $special = '') {
	//TODO pouvoir signer propal directmeent à ce niveau ?
	
	global $db,$langs,$user,$conf;
	
	$p=new Propal($db);
	if($p->fetch($fk_propal)) {
		
		$p->array_options['options_proba'] = (int)$proba;
		$p->array_options['options_date_cloture_prev'] = strtotime('+'.$nb_month.' month' );
		
		
		$p->update_extrafields($user);
	}
	
	
}	

function _propasals($min,$max,$start,$end,$special='',$fk_user = 0) {
	global $db,$langs,$user,$conf;
	
	
	/*
	 * Retourne les propals avec un taux adéquat
	 */
	
	$PDOdb=new TPDOdb; 
	
	if(!empty($special) && empty($start)) {
			
		if($special=='signed') {
			$sql = "SELECT p.rowid FROM ".MAIN_DB_PREFIX."propal p	WHERE p.fk_statut = 2 AND p.date_valid> (NOW() - INTERVAL 30 DAY) "; 
			
		}
		else if($special=='notsigned') {
			$sql = "SELECT p.rowid FROM ".MAIN_DB_PREFIX."propal p	WHERE p.fk_statut = 3 AND p.date_valid> (NOW() - INTERVAL 30 DAY)"; 
			
		}
		
					
	}
	else{
		$sql ="SELECT p.rowid FROM ".MAIN_DB_PREFIX."propal p
				LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields ex ON (ex.fk_object = p.rowid)
				WHERE fk_statut=1 ";
				
		if(empty($min)) $sql.=" AND (ex.proba >=0 OR ex.proba IS NULL) AND (ex.proba < ".(int)$max. " OR ex.proba IS NULL)";
		else $sql.=" AND ex.proba < ".(int)$max. " AND ex.proba>=".(int)$min;
		
		if(empty($start)) {
			$sql.=" AND (ex.date_cloture_prev IS NULL OR ex.date_cloture_prev < NOW() ) ";
		}
		else {
			$sql.=" AND (ex.date_cloture_prev >= (NOW() + INTERVAL ".$start." MONTH) ) ";			
		}
		
		if($end>0) {
			$sql.=" AND (ex.date_cloture_prev < (NOW() + INTERVAL ".$end." MONTH) ";
			if(empty($start)) { $sql.=" OR ex.date_cloture_prev IS NULL "; }
			$sql.=" ) ";			
		}
		 	  	 
	}
	
	if($fk_user>0) {
		$sql.= " AND p.fk_user_author = ".$fk_user;
	}
	
	$TRes = $PDOdb->ExecuteAsArray($sql); 
				
	$Tab=array();
	
	foreach($TRes as &$row) {
		
		$p=new Propal($db);
		if($p->fetch($row->rowid)>0) {
			$soc = new Societe($db);
			$soc->fetch($p->socid);
			
			$obj = new stdClass;
			
			$obj->id = $p->id;
			$obj->ref = $p->ref;
			$obj->total_ht_aff = price($p->total_ht);
			$obj->customerLink = $soc->getNomUrl(1);
			$obj->link = $p->getNomUrl(1);
			$obj->total_ht = $p->total_ht;

			$Tab[] = $obj;
			
		}
		
	}
	
	
	
	return $Tab;
	
}

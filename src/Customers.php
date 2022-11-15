<?php

namespace CustomerListExport;

class Customers {
	public $wpdb;

	public function __construct() {
		$this->wpdb = $GLOBALS['wpdb'];
	}

	public function fetchData($source = 'billing') {
		// Grab all the meta values
		$this->wpdb->query('SET SESSION group_concat_max_len = 10000');

		$query = " SELECT p.*, 
			GROUP_CONCAT(pm.meta_key ORDER BY pm.meta_key DESC SEPARATOR '||') as meta_keys, 
			GROUP_CONCAT(pm.meta_value ORDER BY pm.meta_key DESC SEPARATOR '||') as meta_values 
			FROM {$this->wpdb->posts} p 
			LEFT JOIN {$this->wpdb->postmeta} pm on pm.post_id = p.ID 
			WHERE p.post_type = 'shop_order'
			GROUP BY p.ID";

		$customers = $this->wpdb->get_results($query);

		$customers = array_map(function ($a) use ($source) {
			// Pull out the meta values
			$data = array_combine(
				explode('||', $a->meta_keys),
				array_map('maybe_unserialize', explode('||', $a->meta_values)),
			);

			// Set the fields we want
			if ($source == 'billing') {
				$data['first_name'] = $data['_billing_first_name'];
				$data['last_name'] = $data['_billing_last_name'];
				$data['address_1'] = $data['_billing_address_1'];
				$data['address_2'] = $data['_billing_address_2'];
				$data['city'] = $data['_billing_city'];
				$data['state'] = $data['_billing_state'];
				$data['zip'] = $data['_billing_postcode'];
			} else {
				$data['first_name'] = $data['_shipping_first_name'];
				$data['last_name'] = $data['_shipping_last_name'];
				$data['address_1'] = $data['_shipping_address_1'];
				$data['address_2'] = $data['_shipping_address_2'];
				$data['city'] = $data['_shipping_city'];
				$data['state'] = $data['_shipping_state'];
				$data['zip'] = $data['_shipping_postcode'];
			}

			// Normalize street suffixes to reduce duplicates
			// Taken from the UP Postal Service
			$street_suffixes = [
				'ALLEY' => 'ALY',
				'ESTATES' => 'EST',
				'LAKES' => 'LKS',
				'RIDGE' => 'RDG',
				'ANNEX' => 'ANX',
				'EXPRESSWAY' => 'EXPY',
				'LANDING' => 'LNDG',
				'RIVER' => 'RIV',
				'ARCADE' => 'ARC',
				'EXTENSION' => 'EXT',
				'LANE' => 'LN',
				'ROAD' => 'RD',
				'AVENUE' => 'AVE',
				'FALL' => 'FALL',
				'LIGHT' => 'LGT',
				'ROW' => 'ROW',
				'BAYOU' => 'YU',
				'FALLS' => 'FLS',
				'LOAF' => 'LF',
				'RUN' => 'RUN',
				'BEACH' => 'BCH',
				'FERRY' => 'FRY',
				'LOCKS' => 'LCKS',
				'SHOAL' => 'SHLS',
				'BEND' => 'BND',
				'FIELD' => 'FLD',
				'LODGE' => 'LDG',
				'SHOALS' => 'SHLS',
				'BLUFF' => 'BLF',
				'FIELDS' => 'FLDS',
				'LOOP' => 'LOOP',
				'SHORE' => 'SHR',
				'BOTTOM' => 'BTM',
				'FLATS' => 'FLT',
				'MALL' => 'MALL',
				'SHORES' => 'SHRS',
				'BOULEVARD' => 'BLVD',
				'FORD' => 'FOR',
				'MANOR' => 'MNR',
				'SPRING' => 'SPG',
				'BRANCH' => 'BR',
				'FOREST' => 'FRST',
				'MEADOWS' => 'MDWS',
				'SPRINGS' => 'SPGS',
				'BRIDGE' => 'BRG',
				'FORGE' => 'FGR',
				'MILL' => 'ML',
				'SPUR' => 'SPUR',
				'BROOK' => 'BRK',
				'FORK' => 'FRK',
				'MILLS' => 'MLS',
				'SQUARE' => 'SQ',
				'BURG' => 'BG',
				'FORKS' => 'FRKS',
				'MISSION' => 'MSN',
				'STATION' => 'STA',
				'BYPASS' => 'BYP',
				'FORT' => 'FRT',
				'MOUNT' => 'MT',
				'STRAVENUE' => 'STRA',
				'CAMP' => 'CP',
				'FREEWAY' => 'FWY',
				'MOUNTAIN' => 'MTN',
				'STREAM' => 'STRM',
				'CANYON' => 'CYN',
				'GARDENS' => 'GDNS',
				'NECK' => 'NCK',
				'STREET' => 'ST',
				'CAPE' => 'CPE',
				'GATEWAY' => 'GTWY',
				'ORCHARD' => 'ORCH',
				'SUMMIT' => 'SMT',
				'CAUSEWAY' => 'CSWY',
				'GLEN' => 'GLN',
				'OVAL' => 'OVAL',
				'TERRACE' => 'TER',
				'CENTER' => 'CTR',
				'GREEN' => 'GN',
				'PARK' => 'PARK',
				'TRACE' => 'TRCE',
				'CIRCLE' => 'CIR',
				'GROVE' => 'GRV',
				'PARKWAY' => 'PKY',
				'TRACK' => 'TRAK',
				'CLIFFS' => 'CLFS',
				'HARBOR' => 'HBR',
				'PASS' => 'PASS',
				'TRAIL' => 'TRL',
				'CLUB' => 'CLB',
				'HAVEN' => 'HVN',
				'PATH' => 'PATH',
				'TRAILER' => 'TRLR',
				'CORNER' => 'COR',
				'HEIGHTS' => 'HTS',
				'PIKE' => 'PIKE',
				'TUNNEL' => 'TUNL',
				'CORNERS' => 'CORS',
				'HIGHWAY' => 'HWY',
				'PINES' => 'PNES',
				'TURNPIKE' => 'TPKE',
				'COURSE' => 'CRSE',
				'HILL' => 'HL',
				'PLACE' => 'PL',
				'UNION' => 'UN',
				'COURT' => 'CT',
				'HILLS' => 'HLS',
				'PLAIN' => 'PLN',
				'VALLEY' => 'VLY',
				'COURTS' => 'CTS',
				'HOLLOW' => 'HOLW',
				'PLAINS' => 'PLNS',
				'VIADUCT' => 'VIA',
				'COVE' => 'CV',
				'INLET' => 'INLT',
				'PLAZA' => 'PLZ',
				'VIEW' => 'VW',
				'CREEK' => 'CRK',
				'ISLAND' => 'IS',
				'POINT' => 'PT',
				'VILLAGE' => 'VLG',
				'CRESCENT' => 'CRES',
				'ISLANDS' => 'ISS',
				'PORT' => 'PRT',
				'VILLE' => 'VL',
				'CROSSING' => 'XING',
				'ISLE' => 'ISLE',
				'PRAIRIE' => 'PR',
				'VISTA' => 'VIS',
				'DALE' => 'DL',
				'JUNCTION' => 'JCT',
				'RADIAL' => 'RADL',
				'WALK' => 'WALK',
				'DAM' => 'DM',
				'KEY' => 'CY',
				'RANCH' => 'RNCH',
				'WAY' => 'WAY',
				'DIVIDE' => 'DV',
				'KNOLLS' => 'KNLS',
				'RAPIDS' => 'RPDS',
				'WELLS' => 'WLS',
				'DRIVE' => 'DR',
				'LAKE' => 'LK',
				'REST' => 'RST',
			];

			$last_word_start = strrpos($data['address_1'], ' ') + 1;
			$last_word = strtoupper(
				substr($data['address_1'], $last_word_start),
			);

			$text = str_replace(
				array_keys($street_suffixes),
				$street_suffixes,
				$last_word,
			);

			$data['address_1'] = substr_replace(
				$data['address_1'],
				$text,
				$last_word_start,
			);

			// Create a hash for easy compare
			$hash = false;
			if (
				!empty($data['last_name']) &&
				!empty($data['address_1']) &&
				!empty($data['city'])
			) {
				$hash = $data['last_name'] . $data['address_1'] . $data['city'];
				$hash = md5(strtoupper($hash));
			}

			$data['hash'] = $hash;

			return $data;
		}, $customers);

		// Remove duplicates
		$processed = [];
		$customers = array_filter($customers, function ($data) use (
			&$processed
		) {
			if ($data['hash'] && !in_array($data['hash'], $processed)) {
				$processed[] = $data['hash'];
				return true;
			} else {
				return false;
			}
		});

		// Sort by last name
		uasort($customers, function ($x, $y) {
			return $x['last_name'] > $y['last_name'];
		});

		return $customers;
	}
}

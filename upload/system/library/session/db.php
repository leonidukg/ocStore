<?php
namespace Session;

final class DB {
	public $maxlifetime;

	public function __construct($registry) {
		$this->db = $registry->get('db');

		$this->maxlifetime = ini_get('session.gc_maxlifetime') !== null ? (int)ini_get('session.gc_maxlifetime') : 1440;

		if (date("N") == 7 && date("G") == 3)
		{ // Clear Sessions only: Sunday, 3 AM
			$this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE expire < DATE_SUB(NOW(), INTERVAL 7 DAY);");
		}
	}

	public function read($session_id) {
		$query = $this->db->query("SELECT `data` FROM `" . DB_PREFIX . "session` WHERE `session_id` = '" . $this->db->escape($session_id) . "' AND `expire` > '" . $this->db->escape(date('Y-m-d H:i:s', time())) . "'");

		if ($query->num_rows) {
			return json_decode($query->row['data'], true);
		} else {
			return false;
		}
	}

	public function write($session_id, $data) {
		if ($session_id) {
			$this->db->query("REPLACE INTO `" . DB_PREFIX . "session` SET `session_id` = '" . $this->db->escape($session_id) . "', `data` = '" . $this->db->escape(json_encode($data)) . "', `expire` = '" . $this->db->escape(date('Y-m-d H:i:s', time() + (int)$this->maxlifetime)) . "'");
		}

		return true;
	}

	public function destroy($session_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE `session_id` = '" . $this->db->escape($session_id) . "'");

		return true;
	}

	public function gc() {
		if (ini_get('session.gc_divisor') && $gc_divisor = (int)ini_get('session.gc_divisor')) {
			$gc_divisor = $gc_divisor === 0 ? 100 : $gc_divisor;
		} else {
			$gc_divisor = 100;
		}

		if (ini_get('session.gc_probability')) {
			$gc_probability = (int)ini_get('session.gc_probability');
		} else {
			$gc_probability = 1;
		}

		if (mt_rand() / mt_getrandmax() > $gc_probability / $gc_divisor) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE `expire` < '" . $this->db->escape(date('Y-m-d H:i:s', time())) . "'");

			return true;
		}
	}
}

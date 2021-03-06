<?php
namespace Hypercharge;

class TransactionUrl implements IUrl {

	private $mode;
	private $action;
	private $channel;

	private static $liveUrl;

	private $urls = array(
		 Config::ENV_LIVE    => 'https://hypercharge.net'
		,Config::ENV_SANDBOX => 'https://test.hypercharge.net'
	);

	function __construct($mode, $channel, $action = 'process') {
		if(!Config::isValidMode($mode)) throw new \Exception('mode must be "sandbox" or "live"');
		$allowedActions = array(
			'process', 'reconcile', 'reconcile/by_date'
			,'recurring/schedules_by_date', 'recurring/unsubscribe', 'recurring/activate', 'recurring/deactivate'
		);
		if($mode == Config::ENV_SANDBOX) {
			array_push($allowedActions
				,'bogus_event/chargeback'
				,'bogus_event/pre_arbitration'
				,'bogus_event/chargeback_reversal'
				,'bogus_event/retrieval'
				,'bogus_event/deposit'
				,'bogus_event/debit_chargeback'
				,'bogus_event/reject'
				,'bogus_event/charge'
			);
		}
		if(!in_array($action, $allowedActions)) throw new \Exception('action must be one of "'.join($allowedActions, '", "').'" but got "'.$action.'"');

		$this->mode    = $mode;
		$this->channel = $channel;
		$this->action  = $action;
	}

	function getUrl() {
		return $this->urls[$this->mode];
	}

	function get() {
		return $this->getUrl().'/'.$this->action.'/'.$this->channel;
	}

}

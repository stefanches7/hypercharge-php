<?php
namespace Hypercharge;

require_once dirname(__DIR__).'/test_helper.php';
if(getenv('DEBUG') == '1') Config::setLogger(new StdoutLogger());

class CurlTest extends HyperchargeTestCase {

	function testPostToInvalidShouldThrowException() {
		$curl = new Curl('user', 'passw');
		$this->expectException(new Errors\NetworkError('http://localhost/eine/falsche/url', 'The requested URL returned error: 404'));
		$curl->xmlPost('http://localhost/eine/falsche/url', '<data />');
	}

	function testPostToValidUrlShouldReturnBody() {
		try {
			$curl = new Curl('user', 'passw');
			$response = $curl->xmlPost('https://test.hypercharge.net/', '');
			//'Sat Apr 27 09:41:53 UTC 2013'
			$this->assertPattern('/^\w\w\w \w\w\w \d\d? \d\d:\d\d:\d\d UTC \d\d\d\d$/', $response);
		}	catch(\Exception $exe)	{
			$this->fail($exe->getMessage());
		}
	}

	function testJsonGetToValidUrlShouldReturnBody() {
		$this->credentials('sandbox');
		try {
			$curl = new Curl($this->credentials->user, $this->credentials->password);
			$response = $curl->jsonGet(new v2\Url('sandbox', 'scheduler?per_page=2'));
			// parsed json
			$this->assertIsA($response, '\StdClass');
			$this->assertEqual('PaginatedCollection', $response->type);
			$this->assertEqual('RecurringSchedule', $response->entries_base_type);
			$this->assertEqual(2, $response->per_page);
			$this->assertEqual(1, $response->current_page);
		}	catch(\Exception $exe)	{
			$this->fail($exe->getMessage());
		}
	}

	function testJsonGetToInValidHostShouldThrow() {
		$this->credentials('sandbox');
		try {
			$curl = new Curl($this->credentials->user, $this->credentials->password);
			$curl->jsonRequest('GET', 'http://www.wrong-hostname.de/foo/bar');
		}	catch(Errors\NetworkError $exe)	{
			$this->assertEqual(10, $exe->status_code);
			$this->assertPattern('/^'.preg_quote('Could not resolve host: www.wrong-hostname.de').'/', $exe->technical_message);
			return;
		}
		$this->fail('expected NetworkError with ');
	}

	function testJsonGetToInValidUrlShouldThrow() {
		$this->credentials('sandbox');
		try {
			$curl = new Curl($this->credentials->user, $this->credentials->password);
			$curl->jsonGet(new v2\Url('sandbox', 'scheduler/123455668798797'));
		}	catch(Errors\NetworkError $exe)	{
			$this->assertEqual('The requested URL returned error: 404', $exe->technical_message);
			return;
		}
		$this->fail('expected NetworkError with 404');
	}

	function testJsonGetUnauthorizedShouldThrow() {
		$this->credentials('sandbox');
		try {
			$curl = new Curl('user', 'password');
			$curl->jsonGet(new v2\Url('sandbox', 'scheduler/123455668798797'));
		}	catch(Errors\NetworkError $exe)	{
			$this->assertEqual('The requested URL returned error: 401', $exe->technical_message);
			return;
		}
		$this->fail('expected NetworkError with 401');
	}

	function testHandleErrorSilentIfCodeLt400() {
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 200, '', null, array());
		$curl->handleError('http://url', 300, '', null, array());
		$curl->handleError('http://url', 303, '', null, array());
	}

	function testHandleError400() {
		$this->expectException('Hypercharge\Errors\InputDataInvalidError');
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 400, JsonSchemaFixture::response('scheduler_error.json'), null, array());
	}

	function testHandleError401() {
		$this->expectException('Hypercharge\Errors\NetworkError');
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 401, '', null, array());
	}

	function testHandleError500() {
		$this->expectException('Hypercharge\Errors\NetworkError');
		$curl = new Curl('user', 'passw');
		$curl->handleError('http://url', 500, '', null, array());
	}
}
<?php

/**
 * Base unit test class for Document Feedback
 */
class DocumentFeedback_TestCase extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		global $document_feedback;
		$this->_toc = $document_feedback;
	}
}

<?php
	class LawReference {
		public static function parse ($lawReferenceString) {
			// $lawReferenceString is something like
			//    "§ 985 BGB"
			// or "985 BGB"
			// or "BGB"
			// or "Art 1 GG"
			// or "Art. 2 III GG"
			// or "Art. 1" (assuming GG if Art and no law given)
			// or "985" (assuming § if not given, assuming BGB if § and no law given)
		}

		public static function isValid($lawReferenceString) {
			return (self::parse($lawReferenceString) !== false);
		}

		private $lawReferenceOrigString;

		private $paragraphType;
		private $paragraph;
		private $subparagraph;
		private $law;

		const $paragraphtypes = [
			['§', 'par', 'par.', 'paragraph', 'paragraf'],
			['Artikel', 'Art', 'Art.', 'A'],
		]

		public function __construct($lawReferenceString) {
			list(
				$this->paragraphType,
				$this->paragraph,
				$this->subparagraph,
				$this->law
			) = self::parse($lawReferenceString);
		}

		public function __toString() {
			return $this->lawReferenceOrigString;
		}
	}

	// The lesson board: a lesson's rows written up one at a time, as a teacher
	// writes down a chalkboard, with the piece of each row that does the job
	// chalked in.  The learner steps it.  It never plays on its own.
	//
	// It reads what the template printed and changes none of it:
	//
	//	<section class="lesson-board" data-strategy="helper word">
	//		<ol class="board-rows">
	//			<li class="board-row">                 a row
	//			<li class="board-row" data-from="1">   a row built from row 1
	//				<span class="board-foreign"><mark>No</mark> hablo español.</span>
	//				<span class="board-english">I do not speak Spanish.</span>
	//		<p class="board-rule">                     the plain sentence, shown last
	//
	// Without this script the same markup is a plain list with its marks, so
	// nothing is lost to a reader with scripts off.

(function () {
	'use strict';

	function LessonBoard(board) {
		this.board = board;
		this.rows = Array.prototype.slice.call(board.querySelectorAll('.board-row'));
		this.rule = board.querySelector('.board-rule');
		this.last = this.rows.length + (this.rule ? 1 : 0);	// one step a row, then the rule
		this.step = 0;

		if(!this.rows.length) {
			return;
		}

		this.build();
		this.show(1);
	}

	LessonBoard.prototype.build = function () {
		var self = this;
		var controls = document.createElement('div');

		controls.className = 'board-controls';

		this.back = this.button('Back', 'board-back', function () { self.show(self.step - 1); });
		this.next = this.button('Next', 'board-next', function () { self.show(self.step >= self.last ? 1 : self.step + 1); });
		this.count = document.createElement('span');
		this.count.className = 'board-count';
		this.count.setAttribute('aria-live', 'polite');

		controls.appendChild(this.back);
		controls.appendChild(this.count);
		controls.appendChild(this.next);
		this.board.appendChild(controls);

		this.board.classList.add('board-stepped');
		this.board.setAttribute('tabindex', '0');
		this.board.addEventListener('keydown', function (event) {
			if(event.key === 'ArrowRight' && self.step < self.last) {
				self.show(self.step + 1);
				event.preventDefault();
			} else if(event.key === 'ArrowLeft' && self.step > 1) {
				self.show(self.step - 1);
				event.preventDefault();
			}
		});
	};

	LessonBoard.prototype.button = function (label, name, action) {
		var button = document.createElement('button');

		button.type = 'button';
		button.className = 'board-button ' + name;
		button.textContent = label;
		button.addEventListener('click', action);

		return button;
	};

		// Everything up to this step is on the board; the newest row is the one
		// being written, and the row it was built from is pointed at.

	LessonBoard.prototype.show = function (step) {
		var index, row, source;

		this.step = Math.max(1, Math.min(step, this.last));

		for(index = 0; index < this.rows.length; index++) {
			row = this.rows[index];
			row.hidden = index >= this.step;
			row.classList.toggle('board-writing', index === this.step - 1);
			row.classList.remove('board-source');
		}

		row = this.rows[this.step - 1];
		source = row ? parseInt(row.getAttribute('data-from'), 10) : 0;

		if(source && this.rows[source - 1]) {
			this.rows[source - 1].classList.add('board-source');
		}

		if(this.rule) {
			this.rule.hidden = this.step < this.last;
		}

		this.back.disabled = this.step === 1;
		this.next.textContent = this.step >= this.last ? 'Start again' : 'Next';
		this.count.textContent = 'Step ' + this.step + ' of ' + this.last;
	};

	function start() {
		var boards = document.querySelectorAll('.lesson-board');
		var index;

		for(index = 0; index < boards.length; index++) {
			new LessonBoard(boards[index]);
		}
	}

	if(document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();

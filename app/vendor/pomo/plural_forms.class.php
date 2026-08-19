<?php
namespace app\vendor\pomo;

/**
 * A gettext Plural-Forms parser.
 *
 * @since 4.9.0
 */
#[AllowDynamicProperties]
class Plural_Forms {
	/**
	 * Operator characters.
	 *
	 * @since 4.9.0
	 * @var string OP_CHARS Operator characters.
	 */
	const OP_CHARS = '|&><!=%?:';

	/**
	 * Valid number characters.
	 *
	 * @since 4.9.0
	 * @var string NUM_CHARS Valid number characters.
	 */
	const NUM_CHARS = '0123456789';

	/**
	 * Operator precedence.
	 *
	 * @since 4.9.0
	 * @var int[] Operator precedence.
	 */
	public $op = array(
		'('  => 0,
		'|'  => 1,
		'&'  => 2,
		'?'  => 3,
		':'  => 3,
		'='  => 4,
		'!'  => 4,
		'>'  => 4,
		'<'  => 4,
		'%'  => 5,
		'!=' => 4,
		'>=' => 4,
		'<=' => 4,
	);

	/**
	 * Temporary value or cache for protected class variables.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	protected $tmpvalue;

	/**
	 * The number of plural forms.
	 *
	 * @since 4.9.0
	 * @var int
	 */
	protected $nplurals;

	/**
	 * The compiled plural form expression.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	protected $plural_expression;

	/**
	 * A flag to hold the current token.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	protected $curr_token;

	/**
	 * The operation stack.
	 *
	 * @since 4.9.0
	 * @var array
	 */
	protected $stack;

	/**
	 * The expression stack.
	 *
	 * @since 4.9.0
	 * @var array
	 */
	protected $expr_stack;

	/**
	 * Parses a gettext Plural-Forms expression.
	 *
	 * @since 4.9.0
	 *
	 * @param string $expression the expression to parse.
	 */
	public function __construct( $expression ) {
		$this->nplurals = 1;
		$this->plural_expression = 'n';
		$this->tmpvalue = '';

		// Parse the expression.
		$this->parse_expression( $expression );
	}

	/**
	 * Evaluates the parsed expression.
	 *
	 * @since 4.9.0
	 *
	 * @param int $n the plural number.
	 * @return int the result.
	 */
	public function get( $n ) {
		$this->tmpvalue = $n;
		return $this->evaluate();
	}

	/**
	 * Parses an expression.
	 *
	 * @since 4.9.0
	 *
	 * @param string $expression the expression to parse.
	 */
	protected function parse_expression( $expression ) {
		// Remove all non-integer characters.
		$expression = str_replace( array( '(', ')' ), ' ', $expression );

		// Remove all non-arithmetic characters.
		$expression = preg_replace( '/\s+/', ' ', $expression );

		// Remove all non-arithmetic characters.
		$expression = preg_replace( '/[^\s0-9|&><!=%?:()\-+*\/]/', '', $expression );

		// Replace the integers with placeholders.
		$expression = preg_replace( '/(\d+)/', ' $1 ', $expression );

		$this->stack     = array();
		$this->expr_stack = array();

		$tokens = explode( ' ', trim( $expression ) );

		foreach ( $tokens as $token ) {
			if ( '' === $token ) {
				continue;
			}

			if ( is_numeric( $token ) ) {
				array_push( $this->expr_stack, (float) $token );
			} else {
				$this->handle_operator( $token );
			}
		}

		if ( count( $this->stack ) > 1 ) {
			$this->curr_token = ')';
			$this->pop_until_opening_paren();
		}
	}

	/**
	 * Handles an operator.
	 *
	 * @since 4.9.0
	 *
	 * @param string $token the operator.
	 */
	protected function handle_operator( $token ) {
		switch ( $token ) {
			case '(':
				array_push( $this->stack, $token );
				break;

			case ')':
				$this->pop_until_opening_paren();
				break;

			case '!':
			case '%':
			case '?':
			case ':':
			case '|':
			case '&':
			case '=':
			case '>':
			case '<':
			case '!=':
			case '>=':
			case '<=':
				$this->push_operator( $token );
				break;

			default:
				throw new \Exception( 'Unknown operator: ' . $token );
		}
	}

	/**
	 * Pushes an operator onto the stack.
	 *
	 * @since 4.9.0
	 *
	 * @param string $operator the operator.
	 */
	protected function push_operator( $operator ) {
		// The operator precedence.
		$precedence = $this->op[ $operator ];

		// If the precedence is lower than the current one, pop the stack.
		while ( ! empty( $this->stack ) && $this->op[ end( $this->stack ) ] >= $precedence ) {
			$this->pop_operator();
		}

		// Push the operator onto the stack.
		array_push( $this->stack, $operator );
	}

	/**
	 * Pops an operator off the stack and evaluates it.
	 *
	 * @since 4.9.0
	 */
	protected function pop_operator() {
		$operator = array_pop( $this->stack );
		$this->evaluate_operator( $operator );
	}

	/**
	 * Evaluates an operator.
	 *
	 * @since 4.9.0
	 *
	 * @param string $operator the operator.
	 */
	protected function evaluate_operator( $operator ) {
		switch ( $operator ) {
			case '!':
				$this->expr_stack[] = ! $this->pop_expr_stack();
				break;

			case '%':
				$this->expr_stack[] = $this->pop_expr_stack() % $this->pop_expr_stack();
				break;

			case '?':
				$false = $this->pop_expr_stack();
				$true  = $this->pop_expr_stack();
				$cond  = $this->pop_expr_stack();
				$this->expr_stack[] = $cond ? $true : $false;
				break;

			case ':':
				$false = $this->pop_expr_stack();
				$true  = $this->pop_expr_stack();
				$this->expr_stack[] = $true;
				break;

			case '|':
				$this->expr_stack[] = $this->pop_expr_stack() || $this->pop_expr_stack();
				break;

			case '&':
				$this->expr_stack[] = $this->pop_expr_stack() && $this->pop_expr_stack();
				break;

			case '=':
				$this->expr_stack[] = $this->pop_expr_stack() === $this->pop_expr_stack();
				break;

			case '!=':
				$this->expr_stack[] = $this->pop_expr_stack() !== $this->pop_expr_stack();
				break;

			case '>':
				$this->expr_stack[] = $this->pop_expr_stack() > $this->pop_expr_stack();
				break;

			case '<':
				$this->expr_stack[] = $this->pop_expr_stack() < $this->pop_expr_stack();
				break;

			case '>=':
				$this->expr_stack[] = $this->pop_expr_stack() >= $this->pop_expr_stack();
				break;

			case '<=':
				$this->expr_stack[] = $this->pop_expr_stack() <= $this->pop_expr_stack();
				break;
		}
	}

	/**
	 * Pops from the expression stack.
	 *
	 * @since 4.9.0
	 *
	 * @return mixed The value popped.
	 */
	protected function pop_expr_stack() {
		return array_pop( $this->expr_stack );
	}

	/**
	 * Pops until the opening parenthesis.
	 *
	 * @since 4.9.0
	 */
	protected function pop_until_opening_paren() {
		while ( ! empty( $this->stack ) ) {
			$operator = array_pop( $this->stack );
			if ( '(' === $operator ) {
				break;
			}
			$this->evaluate_operator( $operator );
		}
	}

	/**
	 * Evaluates the expression.
	 *
	 * @since 4.9.0
	 *
	 * @return int the result.
	 */
	protected function evaluate() {
		// Pop all the operators off the stack.
		while ( ! empty( $this->stack ) ) {
			$this->pop_operator();
		}

		// Return the value.
		return (int) $this->pop_expr_stack();
	}
}

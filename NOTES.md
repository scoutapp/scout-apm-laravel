### Developing Locally

Developing locally, have both laravel and php checked out, then add a composer.json repositories section:

    "repositories": [
            {
                    "type": "path",
                    "url": "/Users/cschneid/Projects/scoutapm-laravel"
            },
            {
                    "type": "path",
                    "url": "/Users/cschneid/Projects/scoutapm-php"
            }
    ]

    then add to the require block: 
    "scoutapp/scoutapm-laravel": "*"


### Instruments:

Capturing route info: Route::current()->uri*() returns something like 'foo/{bar?}'

There's a ton of other stuff in the Route object too, but it's hard to get a listing/debug listing of it.

### Installing:

composer.json says what ServiceProvider to look for

ScoutApmServiceProvider register is called, then boot is called later in the sequence.

php artisan vendor:publish, then select scoutapm -- this copies the config file over from the package, into the laravel project's config/ dir.

### Using:

#### To inject into a controller for any manual creating of spans:

	protected $agent;

	public function __construct(\Scoutapm\Agent $agent)
	{
		$this->agent = $agent;
	}


        public function index($foo='defaultfoo') {
                    $span = $this->agent->startSpan("foo");
                    $this->agent->stopSpan();

            return view('welcome', [
                'route' => Route::current(),
                'foo' => $span,
            ]);
        }


#### To use a fascade for the same result:

use ScoutApm;

    public function index($foo='defaultfoo') {
        $span = ScoutApm::startSpan("foo");
        ScoutApm::stopSpan();

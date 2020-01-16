
  
# Laravel MongoDB GridFS

[![Latest Stable Version](https://poser.pugx.org/mts88/laravel-zipkin/v/stable)](https://packagist.org/packages/mts88/laravel-zipkin)
[![Total Downloads](https://poser.pugx.org/mts88/laravel-zipkin/downloads)](https://packagist.org/packages/mts88/laravel-zipkin)
[![License](https://poser.pugx.org/mts88/laravel-zipkin/license)](https://packagist.org/packages/mts88/laravel-zipkin)

A library wants to help the use Openzipkin for Laravel. 

# Table of contents
* [Installation](#installation)
* [Configuration](#configuration)
* [Automatic API tracing](#automatic-api-tracing)
* [Usage](#usage)
	* [Dependency](#dependency)
	* [BaseController](#basecontroller)
	* [Create trace and rootSpan](#create-trace-and-rootspan)
	* [Child span](#child-span)
* [Contact](#contact)
* [License](#license)
# Installation

### Laravel version Compatibility

| Laravel | Package |
|--|--|
| 5.x | not tested |
| >= 6.x.x | 1.0.x |

### Requirements
No requirements are necessary.

### Installation
1. Installation using composer:
```
composer require mts88/laravel-zipkin
```
2. And add the service provider in  `config/app.php`:
```php
Mts88\LaravelZipkin\Providers\LaravelZipkinServiceProvider::class
```
3. You may also register an alias for the ZipkinService by adding the following to the alias array in  `config/app.php`:
```php
'Zipkin'       => Mts88\LaravelZipkin\Facades\Zipkin,
```

## Configuration

Run the command below to publish the package config file  `config/zipkin.php`:
```php
php artisan vendor:publish
```
in your `.env` file define these parameters and set up your configuration: 
```php
ZIPKIN_HOST=http://localhost
ZIPKIN_PORT=9411
```

## Automatic API tracing
The library offer an automatic tracing of request, in particular about your API.  In order to use this automatic tracing you have:
1. Insert the middleware in your `app/Kernel.php` where do you want automatic tracing. For example you can use in api block:
```php
'api' => [
	'throttle:60,1',
	'bindings',
	// Others middleware
	\Mts88\LaravelZipkin\Middlewares\ZipkinRequestLogger::class,
],
```
2. **Each controller** of Api must extends `ZipkinBaseController` (check [BaseController](#basecontroller)):
```php
class MyApiController extends ZipkinBaseController{
// My Api Methods
}
```
## Usage
### Dependency
You can easly access to ZipkinService by dependency injection. In your `Controller` you can access in this way:
```php
use Mts88\LaravelZipkin\Services\ZipkinService;

class MyAwesomeController extends Controller{
	public  function  __construct(ZipkinService  $zipkinService)
	{
		// Do something with $zipkinService
	}
}
```

### BaseController
If you want to automatize child span between controllers and methods, you can use `ZipkinBaseController` and foreach method called in Controller he create automatically a span for the current rootSpan instance.
**note**: ZipkinBaseController doen't create rootSpan, so you have to create before methods are called. For example in middleware.
```php
use Mts88\LaravelZipkin\Controllers\ZipkinBaseController;

class MyAwesomeController  extends  ZipkinBaseController {
	// My Awesome Methods
}
```
In this way you don't need to access to `$zipkinService` in `__construct`, but if you need to override it you **have** to call parent constructor:
```php
use Mts88\LaravelZipkin\Services\ZipkinService;
use Mts88\LaravelZipkin\Controllers\ZipkinBaseController;

class MyAwesomeController extends ZipkinBaseController{

	public  function  __construct(ZipkinService  $zipkinService)
	{
		parent::__construct($zipkinService);
		// Do something with your override
	}
}
```

**note**: in your `MyAwesomeController` now you can access to ZipkinService with public variable of `ZipkinBaseController` :
```php
	// zipkinService instance
	$this->zipkinService;	
```

### Create trace and rootSpan
In order to create a rootSpan you can use this code
```php
$this->zipkinService = new ZipkinService(); // or you can access in others way

// Create trace
$this->zipkinService->setTracer('my_trace_name', $request->ip());

$tags = [
	"my_value1" => "hello",
	"my_value2" => "world"
];

// Create RootSpan
$this->zipkinService->createRootSpan('root_span_of_request', $tags);

// Set Annotation
$this->zipkinService->setRootSpanAnnotation('my_annotation_1', \Zipkin\Timestamp\Timestamp\now());

// Dedicated Tags Methods
$this->zipkinService->setRootSpanMethod('GET') // Method of request
	->setRootSpanPath('/') // Path of request
	->setRootSpanStatusCode("200") // Response Code Server
	->setRootAuthUser(Auth::user()); // User that perform request
	
// Insert others tags
$this->setRootSpanTag('my_value3', "ciao")
	->setRootSpanTag('my_value4', "mondo");


// Close rootSpan and tracer
$this->zipkinService->closeSpan();
```

### Child span
To create a child span:
```php
// Create tracer
$tracing = $this->zipkinService->createTracing('child_span_tracing', $request->ip());
$tracer = $tracing->getTracer(); 

// Create Span
$span = $tracer->nextSpan($this->zipkinService->getRootSpanContext());
$span->annotate("Start", \Zipkin\Timestamp\Timestamp\now());
$span->setName('Child span method');
$span->start(\Zipkin\Timestamp\Timestamp\now());

// Create Tag for Child Span
$span->tag("my_tag_1", 'Hello');
$span->tag("my_tag_2", 'World');
// Make annotation
$span->annotate("End", \Zipkin\Timestamp\Timestamp\now());

// Close Span
$span->finish(\Zipkin\Timestamp\Timestamp\now());
$tracer->flush();
```

## Contact
Open an issue on GitHub if you have any problems or suggestions.
## License
The contents of this repository is released under the  [MIT license](http://opensource.org/licenses/MIT).
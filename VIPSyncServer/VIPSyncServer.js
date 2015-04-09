// Define requires
var http = require('http');
var fs = require('fs-extra');
var dl = require('download');
var url = require('url');

// Parameters for status route
var downloading = false;
var progress = 0;
var success = false;

// Define and configure the http server
http.createServer(function(req, res) {
	
	// Set CORS headers
	res.setHeader('Access-Control-Allow-Origin', '*');
	res.setHeader('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS');
	res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Content-Length, X-Requested-With');

	// Parse the url for query parameters
	var parts = url.parse(req.url, true);
	var path = parts.pathname;
	var query = parts.query;

	// Define API routes
	if(path == '/') {
		res.writeHead(200, {'Content-Type': 'text/html'});
		res.end("Welcome to the VIP QuickStart Sync Server");
	}

	if(path == '/download-package') {
		// Reset everything
		downloading = false;
		progress = 0;
		success = false;

		console.log("Download route");

		downloadPackage(query['url'], "../../tmp/sync/", function(download) { 
			// Callback triggered when download starts successfully
			console.log(download['status']);
			res.writeHead(download['status'], {'Content-Type': 'application/json'});
			res.end( JSON.stringify( { status: download['status'] } ) );
		});
	}

	if(path == '/download-status') {
		console.log("Status route");

		res.writeHead(200, {'Content-Type': 'application/json'});
		
		var responseJSON = {
			downloading: downloading,
			progress: progress,
			success: success
		}

		console.log(responseJSON);

		res.end( JSON.stringify(responseJSON) );
	}

}).listen(3000); // Start the http server on port 3000



console.log("### Welcome to the VIP Sync Server ###\nListening on :3000");



var downloadPackage = function(url, destination, callback) {

	var download = new dl({extract: true, strip: 1, mode: '755'}).get(url).dest(destination);
	
	// Track the progress of the DL
	download.use(function(res) {

		// Get the total download size
		var total = parseInt(res.headers['content-length'], 10);

		res.on('data', function(data) {

			var increment = data.length/total * 100
			progress += increment;
			console.log( Math.floor(progress) ); // Math.floor to make the console output pretty
		});
	});

	downloading = true;

	try {
		// Remove any old SQL files
		prepareDestination(destination, function(removed) {
			
			if(removed) {
				download.run(function (err, files) {

					// Calls back when DL is finished
				    if (!err) {
					 	progress = 100;
					 	downloading = false;
					 	success = true;
					    console.log('File downloaded successfully!');
				    }
				 	else { throw new Error("Download operation failed") } 
				});
			}
			else {
				throw "Recursive directory removal failed";
			}
		});
	}
	catch(err) {
		downloading = false;
		success = false;

		console.log("Error: " + err);
		callback({status: 400, reason: err});
		return;
	}

	callback( {status: 200} ); // Trigger callback if err is not thrown
}

var prepareDestination = function(path, callback) // Calls back true if successful, false if error
{
	console.log("Preparing destination folder...");

	var exists = false;

	try {
		// Sync because it's a quick operation most of the time
		if( fs.lstatSync(path).isDirectory() ) {
			exists = true
		}
	}
	catch(err) {
		console.log("/tmp/sync does not exist.. creating now");
	}

	if(exists) {
		// fs.remove is from the fs-extra module
		fs.remove(path, function(err) {
			if(!err) {
				// re-create the empty dir
				fs.mkdir(path, function(err) {
					if(err) { callback(false) }
					else { callback(true) }
				});
			}
			else {
				console.log(err);
				callback(false);
			}
		});
	}
	else {

		fs.mkdir(path, function(err) {
			if(err) { callback(false) }
			else { callback(true) }
		});
	}
}

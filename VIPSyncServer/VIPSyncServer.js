
var http = require('http');
var fs = require('fs-extra');
var dl = require('download');
var bodyParser = require('body-parser');
var url = require('url');

// TODO: Reset these when download finishes
var downloading = false;
var progress = 0;
var success = false;

http.createServer(function(req, res)
{
	res.setHeader('Access-Control-Allow-Origin', '*');
	res.setHeader('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS');
	res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Content-Length, X-Requested-With');

	var parts = url.parse(req.url, true);
	var path = parts.pathname;
	var query = parts.query;
	var headers = req.headers;

	if(path == '/')
	{
		res.writeHead(200, {'Content-Type': 'text/html'});
		res.end("Welcome to the VIP QuickStart Sync Server");
	}

	if(path == '/download-package')
	{
		// Reset everything
		downloading = false;
		progress = 0;
		success = false;

		console.log("Download route");
		downloadPackage(query['url'], "../../tmp/sync/", function(download) // Callback triggered when download starts
		{
			console.log(download['status']);
			res.writeHead(download['status'], {'Content-Type': 'application/json'});
			res.end("{status: " + download['status'] + "}");
		});
	}

	if(path == '/download-status')
	{
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

}).listen(3000)



console.log("Listening on :3000");



var downloadPackage = function(url, destination, callback)
{
	var download = new dl({extract: true, strip: 1, mode: '755'}).get(url).dest(destination);
	
	download.use(function(res) // Determine the progress of the DL
	{
		var total = parseInt(res.headers['content-length'], 10);

		res.on('data', function(data)
		{
			var increment = data.length/total * 100
			progress += increment;
			console.log( Math.floor(progress) );
		});
	});

	downloading = true;

	try
	{
		prepareDestination(destination, function(removed) // Remove any old SQL files
		{
			if(removed)
			{
				download.run(function (err, files) // Calls back when DL is finished
				{
				    if (err) 
				    {
				        throw new Error("Download operation failed");
				    }
				 	
				 	progress = 100;
				 	downloading = false;
				 	success = true;
				    console.log('File downloaded successfully!');
				});
			}
			else
			{
				throw "Recursive directory removal failed";
			}
		});
		
	}
	catch(err)
	{
		downloading = false;
		success = false;

		console.log("Error: " + err);
		callback({status: 400, reason: err});
		return;
	}

	callback({status: 200}); // Trigger callback if err is not thrown
}

var prepareDestination = function(path, callback) // Calls back true if successful, false if error
{
	console.log("Preparing destination folder...");

	var exists = false;

	try
	{
		if( fs.lstatSync(path).isDirectory() ) // Sync because it's a quick operation most of the time
		{
			exists = true
		}
	}
	catch(err)
	{
		console.log("/tmp/sync does not exist.. creating now");
	}

	if(exists)
	{
		fs.remove(path, function(err) // fs.remove is from the fs-extra module
		{
			if(!err) 
			{
				fs.mkdir(path, function(err) // re-create the empty dir
				{
					if(err) { callback(false) }
					else { callback(true) }
				});
			}
			else
			{
				console.log(err);
				callback(false);
			}
		});
	}
	else
	{
		fs.mkdir(path, function(err)
		{
			if(err) { callback(false) }
			else { callback(true) }
		});

	}
}

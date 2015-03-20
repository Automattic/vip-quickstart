
var http = require('http');
var fs = require('fs');
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
		console.log("Download route");
		downloadPackage(query['url'], "downloads/", function(download) // Callback triggered when download starts
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

	if(path == '/download-success') // Will probably get rid of this b/c functionality is present in status route
	{
		console.log("Success route");
		res.writeHead(200, {'Content-Type': 'application/json'});
		
		var responseJSON = {
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
			var increment = Math.floor(data.length/total * 100)
			console.log( progress += increment );
		});
	});

	downloading = true;

	try
	{
		download.run(function (err, files) // Calls back when DL is finished
		{
		    if (err) 
		    {
		        throw err;
		    }
		 	
		 	progress = 100;
		 	downloading = false;
		 	success = true;
		    console.log('File downloaded successfully!');
		});
	}
	catch(err)
	{
		callback({status: 400});
		return;
	}

	callback({status: 200}); // Trigger callback if err is not thrown
}
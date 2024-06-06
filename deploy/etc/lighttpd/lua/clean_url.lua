local uri_prefix = lighty.req_env["URI_PREFIX"] or "/"

function file_exists(path)
	return lighty.stat(path) and true or false
end

function remove_prefix(str, prefix)
	return str:sub(1, #prefix) == prefix and str:sub(#prefix+1) or str
end

local rel_path = remove_prefix(lighty.env["physical.path"], lighty.env["physical.doc-root"])
if (not file_exists(lighty.env["physical.path"]) and not rel_path:find("^index.php")) then
	lighty.env["uri.path"]          =  uri_prefix .. "index.php/" .. rel_path
	lighty.env["physical.rel-path"] = "/index.php/" .. rel_path
	lighty.env["physical.path"]     = lighty.env["physical.doc-root"] .. remove_prefix(lighty.env["physical.rel-path"], "/")
end
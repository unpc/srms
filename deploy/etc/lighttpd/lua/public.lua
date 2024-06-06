local uri_prefix = lighty.req_env["URI_PREFIX"] or "/"

local legal_exts = {"ico", "png", "jpg", "gif", "swf", "css", "js"}

-- 重定向public
function file_exists(path)
	return lighty.stat(path) and true or false
end
function remove_prefix(str, uri_prefix)
	return str:sub(1, #uri_prefix) == uri_prefix and str:sub(#uri_prefix+1) or str
end

function in_table ( e, t )
	for _,v in pairs(t) do
		if (v==e) then return true end
	end
	return false
end

local rel_path = remove_prefix(lighty.env["physical.path"], lighty.env["physical.doc-root"])
local st,ed = rel_path:find("%..+$")
local ext = st and rel_path:sub(st+1) or ""
ext = ext:lower()
if not file_exists(lighty.env["physical.path"]) 
	and not rel_path:find("^index.php") 
	and in_table(ext, legal_exts)
then
	local file = remove_prefix(lighty.env["uri.path"], uri_prefix)
	lighty.env["uri.path"] = uri_prefix .. "index.php/public"

	local uri_query = lighty.env["uri.query"] or ""
	lighty.env["uri.query"] = uri_query .. (uri_query ~= "" and "&" or "") .. "f=" .. file
	lighty.env["physical.rel-path"] = "/index.php/public"
	lighty.env["physical.path"]     = lighty.env["physical.doc-root"] .. remove_prefix(lighty.env["physical.rel-path"], "/")
end



function file_exists(path)
	local attr = lighty.stat(path)
	return attr and true or false
end

if (not file_exists(lighty.env["physical.path"])) then

	function remove_prefix(str, prefix)
		return str:sub(1, #prefix) == prefix and str:sub(#prefix+1)
	end

	local uri_prefix = lighty.req_env["URI_PREFIX"] or "/"

	local rel_path = remove_prefix(lighty.env["physical.path"], lighty.env["physical.doc-root"])
	local st,ed = rel_path:find("^[^/]+")
	if not st then
		return 404
	end

	local lab_id = rel_path:sub(st,ed)
	lighty.req_env["LAB_ID"] = lab_id
	rel_path = remove_prefix(rel_path, lab_id .. "/")
	if not rel_path then
		lighty.env["uri.path"] = lighty.env["uri.path"] .. "/"
		rel_path = ""
	end


	lighty.req_env["URI_PREFIX"] = "/" .. lab_id .. uri_prefix

	lighty.env["physical.rel-path"] = "/" .. rel_path
	lighty.env["physical.path"] = lighty.env["physical.doc-root"] .. remove_prefix(lighty.env["physical.rel-path"], "/")

end

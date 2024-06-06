-- ref: http://wiki.rtmpd.com/documentation

configuration=
{
	daemon=true,
	pathSeparator="/",

	logAppenders=
	{
		{
			name="console appender",
			type="coloredConsole",
			level=6
		},
		{
			name="file appender",
			type="file",
			level=3, -- INFO level
			fileName="/var/log/crtmp/server.log",
				-- 1. 如果在 log 目录不存在, crtmp 不会自动创建目录, 会导致服务起不来
				-- 2. log 总不在一个文件里记, 而是多文件, 每文件加后缀, 需解决
		}
	},
	
	applications=
	{
		rootDirectory="applications",
		{
			description="Live Broadcast",
			name="flvplayback",
			protocol="dynamiclinklibrary",
			default=true,
			aliases=
			{
				"live"
			},
			acceptors = 
			{
				{
					ip="0.0.0.0",
					port=1935,
					protocol="inboundRtmp"
				},
				--[[{
				ip="0.0.0.0",
				port=8081,
				protocol="inboundRtmps",
				sslKey="server.key",
				sslCert="server.crt"
				},
				{
				ip="0.0.0.0",
				port=8080,
				protocol="inboundRtmpt"
				},
				{
					ip="0.0.0.0",
					port=6666,
					protocol="inboundLiveFlv",
					waitForMetadata=true,
				}
				,
				{
					ip="0.0.0.0",
					port=9999,
					protocol="inboundTcpTs"
				},
				{
				ip="0.0.0.0",
					port=554,
					protocol="inboundRtsp"
				},]]--
			},
			externalStreams = 
			{
				--[[{
					uri="rtmp://edge01.fms.dutchview.nl/botr/bunny",
					localStreamName="test1",
				}]]--
			},
			validateHandshake=false,
			keyframeSeek=false,
			seekGranularity=0.1, --in seconds, between 0.1 and 600
			clientSideBuffer=5, --in seconds, between 5 and 30
			--generateMetaFiles=true, --this will generate seek/meta files on application startup
			--renameBadFiles=false,
			mediaFolder="./media",
			--authentication=
			--{
			--	rtmp={
			--		type="adobe",
			--		encoderAgents=
			--		{
			--			"FMLE/3.0 (compatible; FMSc/1.0)",
			--		},
			--		usersFile="./users.lua"
			--	},
			--	rtsp={
			--		usersFile="./users.lua"
			--	}
			--},
		},
	}
}

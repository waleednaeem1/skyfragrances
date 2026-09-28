import http from 'node:http'
import zlib from 'node:zlib'

const upstream = new URL(process.argv[2] || 'http://127.0.0.1:8091')
const port = Number(process.argv[3] || 8093)
const compressible = /^(text\/html|application\/json|text\/plain|application\/xml|image\/svg\+xml)/i

http.createServer((req, res) => {
  const wantsGzip = /gzip/i.test(req.headers['accept-encoding'] || '')
  const headers = { ...req.headers, host: upstream.host }
  const up = http.request({ hostname: upstream.hostname, port: upstream.port, path: req.url, method: req.method, headers }, (ur) => {
    const type = ur.headers['content-type'] || ''
    const alreadyEncoded = !!ur.headers['content-encoding']
    const out = { ...ur.headers }
    if (wantsGzip && !alreadyEncoded && compressible.test(type)) {
      delete out['content-length']
      out['content-encoding'] = 'gzip'
      out['vary'] = out['vary'] ? out['vary'] + ', Accept-Encoding' : 'Accept-Encoding'
      res.writeHead(ur.statusCode, out)
      ur.pipe(zlib.createGzip({ level: 6 })).pipe(res)
      return
    }
    res.writeHead(ur.statusCode, out)
    ur.pipe(res)
  })
  up.on('error', (e) => { res.writeHead(502); res.end('proxy: ' + e.message) })
  req.pipe(up)
}).listen(port, '127.0.0.1', () => console.log(`gzip proxy 127.0.0.1:${port} -> ${upstream.origin}`))

const RIBBON_VERT = `#version 300 es
precision highp float;
in vec3 aCenter;
in vec3 aDir;
in vec2 aUv;
uniform mat4 uProj;
uniform mat4 uView;
uniform float uRadius;
uniform float uTime;
uniform float uIdx;
uniform vec3 uBreathe;
uniform float uScale;
uniform vec2 uOffset;
out vec2 vUv;
void main() {
  vec3 c = aCenter;
  c.y += sin(c.x * uBreathe.y + uTime * uBreathe.z + uIdx) * uBreathe.x;
  vec4 p = uView * vec4((c + aDir * uRadius) * uScale, 1.0);
  p.xy += uOffset;
  vUv = aUv;
  gl_Position = uProj * p;
}`;

const RIBBON_FRAG = `#version 300 es
precision highp float;
in vec2 vUv;
uniform vec3 uGold;
uniform vec3 uAmber;
uniform float uTime;
uniform float uSpeed;
uniform float uFreq;
uniform float uIdx;
uniform float uHalo;
uniform float uHaloAlpha;
uniform float uFade;
uniform float uGain;
uniform vec4 uPulse;
out vec4 outColor;
void main() {
  float m = sin(vUv.x * uFreq - uTime * uSpeed) * 0.5 + 0.5;
  vec3 col = mix(uGold, uAmber, m);
  float head = fract(uTime * uPulse.x + uIdx * 0.25);
  float d = vUv.x - head;
  float pulse = exp(-d * d * uPulse.y);
  col = min(mix(col, mix(uGold, uAmber, uPulse.z), pulse * 0.5) * (1.0 + pulse * uPulse.w), uAmber * 1.15);
  float a = smoothstep(0.0, 0.15, vUv.x) * smoothstep(1.0, 0.85, vUv.x) * pow(sin(vUv.y * 3.14159265), 1.8);
  a *= uFade * mix(1.0, uHaloAlpha, uHalo) * uGain;
  outColor = vec4(col * a, a);
}`;

const SPRITE_VERT = `#version 300 es
precision highp float;
in vec2 aPos;
uniform mat4 uProj;
uniform mat4 uView;
uniform float uSize;
uniform float uScale;
uniform vec2 uOffset;
uniform vec2 uAt;
out vec2 vP;
void main() {
  vec4 p = uView * vec4((aPos * uSize + uAt) * uScale, -0.6 * uScale, 1.0);
  p.xy += uOffset;
  vP = aPos;
  gl_Position = uProj * p;
}`;

const SPRITE_FRAG = `#version 300 es
precision highp float;
in vec2 vP;
uniform vec3 uColor;
uniform float uOpacity;
out vec4 outColor;
void main() {
  float r2 = dot(vP, vP);
  float g = exp(-r2 * 3.0) * smoothstep(1.0, 0.6, sqrt(r2));
  float a = g * uOpacity;
  outColor = vec4(uColor * a, a);
}`;

function hexToRgb(hex) {
  const n = parseInt(String(hex).replace('#', ''), 16);
  return [((n >> 16) & 255) / 255, ((n >> 8) & 255) / 255, (n & 255) / 255];
}

function perspective(fovDeg, aspect, near, far) {
  const f = 1 / Math.tan((fovDeg * Math.PI) / 360);
  const nf = 1 / (near - far);
  return new Float32Array([f / aspect, 0, 0, 0, 0, f, 0, 0, 0, 0, (far + near) * nf, -1, 0, 0, 2 * far * near * nf, 0]);
}

function viewMatrix(rx, ry, ty, camZ) {
  const cx = Math.cos(rx);
  const sx = Math.sin(rx);
  const cy = Math.cos(ry);
  const sy = Math.sin(ry);
  return new Float32Array([cy, sx * sy, -cx * sy, 0, 0, cx, sx, 0, sy, -sx * cy, cx * cy, 0, 0, ty, -camZ, 1]);
}

function catmullRom(points, samples) {
  const n = points.length;
  const out = [];
  for (let s = 0; s <= samples; s++) {
    const u = (s / samples) * (n - 1);
    const seg = Math.min(Math.floor(u), n - 2);
    const t = u - seg;
    const p0 = points[Math.max(seg - 1, 0)];
    const p1 = points[seg];
    const p2 = points[seg + 1];
    const p3 = points[Math.min(seg + 2, n - 1)];
    const t2 = t * t;
    const t3 = t2 * t;
    const v = [0, 0, 0];
    for (let k = 0; k < 3; k++) {
      v[k] = 0.5 * (2 * p1[k] + (-p0[k] + p2[k]) * t + (2 * p0[k] - 5 * p1[k] + 4 * p2[k] - p3[k]) * t2 + (-p0[k] + 3 * p1[k] - 3 * p2[k] + p3[k]) * t3);
    }
    out.push(v);
  }
  return out;
}

function normalize(v) {
  const l = Math.hypot(v[0], v[1], v[2]) || 1;
  return [v[0] / l, v[1] / l, v[2] / l];
}

function cross(a, b) {
  return [a[1] * b[2] - a[2] * b[1], a[2] * b[0] - a[0] * b[2], a[0] * b[1] - a[1] * b[0]];
}

function dot(a, b) {
  return a[0] * b[0] + a[1] * b[1] + a[2] * b[2];
}

function buildTube(points, tubular, radial) {
  const centers = catmullRom(points, tubular);
  const stride = 8;
  const rings = tubular + 1;
  const around = radial + 1;
  const data = new Float32Array(rings * around * stride);
  let normal = null;
  for (let i = 0; i < rings; i++) {
    const prev = centers[Math.max(i - 1, 0)];
    const next = centers[Math.min(i + 1, tubular)];
    const tangent = normalize([next[0] - prev[0], next[1] - prev[1], next[2] - prev[2]]);
    if (!normal) {
      const seed = Math.abs(tangent[1]) < 0.9 ? [0, 1, 0] : [1, 0, 0];
      normal = normalize(cross(cross(tangent, seed), tangent));
    } else {
      const d = dot(normal, tangent);
      normal = normalize([normal[0] - tangent[0] * d, normal[1] - tangent[1] * d, normal[2] - tangent[2] * d]);
    }
    const binormal = cross(tangent, normal);
    for (let j = 0; j < around; j++) {
      const angle = (j / radial) * Math.PI * 2;
      const ca = Math.cos(angle);
      const sa = Math.sin(angle);
      const o = (i * around + j) * stride;
      data[o] = centers[i][0];
      data[o + 1] = centers[i][1];
      data[o + 2] = centers[i][2];
      data[o + 3] = normal[0] * ca + binormal[0] * sa;
      data[o + 4] = normal[1] * ca + binormal[1] * sa;
      data[o + 5] = normal[2] * ca + binormal[2] * sa;
      data[o + 6] = i / tubular;
      data[o + 7] = j / radial;
    }
  }
  const index = new Uint16Array(tubular * radial * 6);
  let k = 0;
  for (let i = 0; i < tubular; i++) {
    for (let j = 0; j < radial; j++) {
      const a = i * around + j;
      const b = a + around;
      index[k++] = a;
      index[k++] = b;
      index[k++] = a + 1;
      index[k++] = b;
      index[k++] = b + 1;
      index[k++] = a + 1;
    }
  }
  return { data, index, count: index.length };
}

function compile(gl, type, source) {
  const shader = gl.createShader(type);
  gl.shaderSource(shader, source);
  gl.compileShader(shader);
  if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
    const log = gl.getShaderInfoLog(shader);
    gl.deleteShader(shader);
    throw new Error('ribbons: shader ' + log);
  }
  return shader;
}

function program(gl, vert, frag, uniforms) {
  const prog = gl.createProgram();
  gl.attachShader(prog, compile(gl, gl.VERTEX_SHADER, vert));
  gl.attachShader(prog, compile(gl, gl.FRAGMENT_SHADER, frag));
  gl.linkProgram(prog);
  if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
    const log = gl.getProgramInfoLog(prog);
    gl.deleteProgram(prog);
    throw new Error('ribbons: link ' + log);
  }
  const u = {};
  uniforms.forEach((name) => {
    u[name] = gl.getUniformLocation(prog, name);
  });
  return { prog, u };
}

function spread(range, i, n) {
  const t = n > 1 ? ((i * 0.618) % 1) : 0;
  return range[0] + (range[1] - range[0]) * t;
}

export default function createRibbons(canvas, cfg, options) {
  const opts = options || {};
  const gl = canvas.getContext('webgl2', { alpha: true, antialias: true, premultipliedAlpha: true, depth: false, stencil: false, powerPreference: 'low-power', preserveDrawingBuffer: !!opts.preserve });
  if (!gl) {
    return null;
  }
  const rc = cfg.ribbons || {};
  const cam = cfg.camera || { fov: 50, z: 5.2 };
  const sprite = cfg.sprite || { opacity: 0.35, y: 0, scale: 2.6 };
  const gold = hexToRgb((rc.colors && rc.colors.gold) || '#C29C6E');
  const amber = hexToRgb((rc.colors && rc.colors.amber) || '#E0A45C');
  const breathe = rc.breathe || { amp: 0.06, freq: 1.4, speed: 0.7 };
  const pulse = rc.pulse || { speed: 0.06, spread: 40, mix: 0.5 };
  const halo = rc.halo || { scale: 2.6, alpha: 0.16 };
  const paths = rc.paths || [];
  const radii = rc.radii || [];
  const tubular = rc.tubular || 72;
  const radial = rc.radial || 6;
  const ribbonProgram = program(gl, RIBBON_VERT, RIBBON_FRAG, ['uProj', 'uView', 'uRadius', 'uTime', 'uIdx', 'uBreathe', 'uScale', 'uOffset', 'uGold', 'uAmber', 'uSpeed', 'uFreq', 'uHalo', 'uHaloAlpha', 'uFade', 'uGain', 'uPulse']);
  const spriteProgram = program(gl, SPRITE_VERT, SPRITE_FRAG, ['uProj', 'uView', 'uSize', 'uScale', 'uOffset', 'uAt', 'uColor', 'uOpacity']);
  const ribbons = paths.map((points, i) => {
    const mesh = buildTube(points, tubular, radial);
    const vao = gl.createVertexArray();
    const vbo = gl.createBuffer();
    const ibo = gl.createBuffer();
    gl.bindVertexArray(vao);
    gl.bindBuffer(gl.ARRAY_BUFFER, vbo);
    gl.bufferData(gl.ARRAY_BUFFER, mesh.data, gl.STATIC_DRAW);
    gl.enableVertexAttribArray(0);
    gl.vertexAttribPointer(0, 3, gl.FLOAT, false, 32, 0);
    gl.enableVertexAttribArray(1);
    gl.vertexAttribPointer(1, 3, gl.FLOAT, false, 32, 12);
    gl.enableVertexAttribArray(2);
    gl.vertexAttribPointer(2, 2, gl.FLOAT, false, 32, 24);
    gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, ibo);
    gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, mesh.index, gl.STATIC_DRAW);
    gl.bindVertexArray(null);
    return { vao, vbo, ibo, count: mesh.count, radius: radii[i] || 0.01, speed: spread(rc.speed || [0.6, 1.6], i, paths.length), freq: spread(rc.freq || [9, 15], i, paths.length), idx: i };
  });
  const quad = { vao: gl.createVertexArray(), vbo: gl.createBuffer() };
  gl.bindVertexArray(quad.vao);
  gl.bindBuffer(gl.ARRAY_BUFFER, quad.vbo);
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, 1, 1, -1, -1, 1, 1, -1, 1]), gl.STATIC_DRAW);
  gl.enableVertexAttribArray(0);
  gl.vertexAttribPointer(0, 2, gl.FLOAT, false, 0, 0);
  gl.bindVertexArray(null);
  gl.disable(gl.DEPTH_TEST);
  gl.enable(gl.BLEND);
  gl.blendFunc(gl.ONE, gl.ONE);
  gl.clearColor(0, 0, 0, 0);
  const state = { w: 1, h: 1, dpr: 1, proj: perspective(cam.fov, 1, 0.1, 40), scale: 1, ox: 0, oy: 0, lost: false, disposed: false };
  let lostHandler = null;
  const onLost = (event) => {
    event.preventDefault();
    state.lost = true;
    if (lostHandler) {
      lostHandler();
    }
  };
  canvas.addEventListener('webglcontextlost', onLost, false);
  function pxPerUnit() {
    return state.h / (2 * cam.z * Math.tan((cam.fov * Math.PI) / 360));
  }
  const api = {
    get lost() {
      return state.lost;
    },
    onLost(fn) {
      lostHandler = fn;
    },
    resize(w, h, dpr) {
      state.w = Math.max(1, w);
      state.h = Math.max(1, h);
      state.dpr = dpr;
      canvas.width = Math.round(state.w * dpr);
      canvas.height = Math.round(state.h * dpr);
      gl.viewport(0, 0, canvas.width, canvas.height);
      state.proj = perspective(cam.fov, state.w / state.h, 0.1, 40);
    },
    setFrame(frame) {
      const ppu = pxPerUnit();
      state.scale = (frame.unitPx || ppu) / ppu;
      state.ox = (frame.originX - state.w / 2) / ppu;
      state.oy = (state.h / 2 - frame.originY) / ppu;
    },
    render(time, pose, layers) {
      if (state.lost || state.disposed) {
        return false;
      }
      const show = layers || {};
      const view = viewMatrix(pose.rotX || 0, pose.rotY || 0, pose.posY || 0, cam.z);
      const fade = typeof pose.fade === 'number' ? pose.fade : 1;
      gl.clear(gl.COLOR_BUFFER_BIT);
      if (show.sprite !== false) {
        const sp = spriteProgram;
        gl.useProgram(sp.prog);
        gl.uniformMatrix4fv(sp.u.uProj, false, state.proj);
        gl.uniformMatrix4fv(sp.u.uView, false, view);
        gl.uniform1f(sp.u.uSize, sprite.scale || 2.6);
        gl.uniform1f(sp.u.uScale, state.scale);
        gl.uniform2f(sp.u.uOffset, state.ox, state.oy);
        gl.uniform2f(sp.u.uAt, 0, sprite.y || 0);
        gl.uniform3f(sp.u.uColor, (gold[0] + amber[0]) / 2, (gold[1] + amber[1]) / 2, (gold[2] + amber[2]) / 2);
        gl.uniform1f(sp.u.uOpacity, (sprite.opacity || 0.35) * fade);
        gl.bindVertexArray(quad.vao);
        gl.drawArrays(gl.TRIANGLES, 0, 6);
      }
      const rp = ribbonProgram;
      gl.useProgram(rp.prog);
      gl.uniformMatrix4fv(rp.u.uProj, false, state.proj);
      gl.uniformMatrix4fv(rp.u.uView, false, view);
      gl.uniform1f(rp.u.uTime, time);
      gl.uniform3f(rp.u.uBreathe, breathe.amp, breathe.freq, breathe.speed);
      gl.uniform1f(rp.u.uScale, state.scale);
      gl.uniform2f(rp.u.uOffset, state.ox, state.oy);
      gl.uniform3f(rp.u.uGold, gold[0], gold[1], gold[2]);
      gl.uniform3f(rp.u.uAmber, amber[0], amber[1], amber[2]);
      gl.uniform4f(rp.u.uPulse, pulse.speed, pulse.spread, pulse.mix, typeof pulse.gain === 'number' ? pulse.gain : 0.5);
      gl.uniform1f(rp.u.uGain, rc.gain || 1.35);
      gl.uniform1f(rp.u.uFade, fade);
      gl.uniform1f(rp.u.uHaloAlpha, halo.alpha);
      ribbons.forEach((r) => {
        gl.bindVertexArray(r.vao);
        gl.uniform1f(rp.u.uIdx, r.idx);
        gl.uniform1f(rp.u.uSpeed, r.speed);
        gl.uniform1f(rp.u.uFreq, r.freq);
        if (show.halo !== false) {
          gl.uniform1f(rp.u.uHalo, 1);
          gl.uniform1f(rp.u.uRadius, r.radius * halo.scale);
          gl.drawElements(gl.TRIANGLES, r.count, gl.UNSIGNED_SHORT, 0);
        }
        if (show.sharp !== false) {
          gl.uniform1f(rp.u.uHalo, 0);
          gl.uniform1f(rp.u.uRadius, r.radius);
          gl.drawElements(gl.TRIANGLES, r.count, gl.UNSIGNED_SHORT, 0);
        }
      });
      gl.bindVertexArray(null);
      return true;
    },
    triangles() {
      return ribbons.reduce((sum, r) => sum + r.count / 3, 0) * 2 + 2;
    },
    dispose() {
      if (state.disposed) {
        return;
      }
      state.disposed = true;
      canvas.removeEventListener('webglcontextlost', onLost, false);
      ribbons.forEach((r) => {
        gl.deleteVertexArray(r.vao);
        gl.deleteBuffer(r.vbo);
        gl.deleteBuffer(r.ibo);
      });
      gl.deleteVertexArray(quad.vao);
      gl.deleteBuffer(quad.vbo);
      gl.deleteProgram(ribbonProgram.prog);
      gl.deleteProgram(spriteProgram.prog);
      const ext = gl.getExtension('WEBGL_lose_context');
      if (ext && !state.lost) {
        ext.loseContext();
      }
    }
  };
  return api;
}

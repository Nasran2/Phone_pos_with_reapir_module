<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

.repair-shell {
  --r-bg: #f8fafc;
  --r-surface: #ffffff;
  --r-border: #e2e8f0;
  --r-text: #0f172a;
  --r-text-muted: #64748b;
  --r-blue: #3b82f6;
  --r-blue-hover: #2563eb;
  --r-indigo: #4f46e5;
  --r-radius: 16px;
  --r-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
  --r-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
  --r-transition: all 0.2s ease-in-out;

  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  max-width: 1600px;
  margin: auto;
  padding: 32px;
  color: var(--r-text);
  min-width: 0;
  background-color: var(--r-bg);
  min-height: 100vh;
}

.repair-shell * {
  box-sizing: border-box;
}

.repair-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 32px; }
.repair-heading h1 { 
    font-size: 30px; 
    font-weight: 700; 
    letter-spacing: -0.5px;
    color: #0f172a;
}
.repair-eyebrow { color: var(--r-indigo); letter-spacing: 1px; font-size: 12px; font-weight: 700; margin-bottom: 6px; text-transform: uppercase; }
.repair-muted { color: var(--r-text-muted); font-size: 14px; line-height: 1.5; font-weight: 400; display: block; }

.repair-nav { display: flex; gap: 8px; border-bottom: 1px solid var(--r-border); overflow: auto; margin-bottom: 32px; padding-bottom: 12px; scrollbar-width: none; }
.repair-nav::-webkit-scrollbar { display: none; }
.repair-nav a { padding: 8px 16px; white-space: nowrap; border-radius: 8px; font-size: 14px; font-weight: 500; color: var(--r-text-muted); transition: var(--r-transition); text-decoration: none; }
.repair-nav a:hover { background: #f1f5f9; color: var(--r-text); }
.repair-nav a.active { background: #f1f5f9; color: var(--r-indigo); font-weight: 600; }

.repair-card {
  background: var(--r-surface);
  border: 1px solid #cbd5e1;
  border-radius: var(--r-radius);
  padding: 32px;
  min-width: 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: var(--r-transition);
}

.repair-card h2, .repair-card h3 { font-weight: 700; font-size: 18px; margin-bottom: 24px; color: #0f172a; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }

.repair-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; }
.repair-grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.repair-grid.four { grid-template-columns: repeat(4, minmax(0, 1fr)); }

.repair-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
.repair-field { display: flex; flex-direction: column; gap: 8px; min-width: 0; font-size: 14px; font-weight: 600; color: #334155; }
.repair-field b { color: #ef4444; }

.repair-shell input:not([type=checkbox]):not([type=radio]):not([type=hidden]),
.repair-shell select,
.repair-shell textarea {
  width: 100%;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 12px 16px;
  color: #0f172a;
  font-size: 15px;
  font-family: inherit;
  min-height: 48px;
  outline: none;
  transition: var(--r-transition);
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.01);
}
.repair-shell input:focus, .repair-shell select:focus, .repair-shell textarea:focus {
  border-color: var(--r-blue);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.repair-button {
  display: inline-flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  color: #0f172a;
  padding: 12px 24px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  border: 1px solid #cbd5e1;
  cursor: pointer;
  white-space: nowrap;
  min-height: 48px;
  transition: var(--r-transition);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  text-decoration: none;
}
.repair-button:hover { background: #f8fafc; border-color: #94a3b8; }
.repair-button:active { transform: translateY(1px); }

.repair-button.primary { background: var(--r-indigo); color: white; border: none; box-shadow: 0 1px 3px rgba(79, 70, 229, 0.3); }
.repair-button.primary:hover { background: #4338ca; }

.repair-metric {
  padding: 24px;
  display: block;
  text-decoration: none;
  position: relative;
  overflow: hidden;
}
.repair-metric:hover { 
  transform: translateY(-4px); 
  box-shadow: var(--r-shadow-hover);
  border-color: #cbd5e1;
}
.repair-metric strong { display: block; font-size: 32px; font-weight: 700; margin: 8px 0 12px; letter-spacing: -1px; }
.repair-metric span { font-size: 13px; font-weight: 500; color: #475569; }
.repair-metric small.repair-muted { display: flex; align-items: center; gap: 4px; font-size: 13px; color: var(--r-text-muted); font-weight: 500; transition: color 0.2s;}
.repair-metric:hover small.repair-muted { color: var(--r-indigo); }

.repair-table { border-collapse: collapse; width: 100%; font-size: 13px; white-space: nowrap; }
.repair-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 600; text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--r-border); border-top: 1px solid var(--r-border); }
.repair-table td { padding: 16px; border-bottom: 1px solid var(--r-border); color: #334155; }
.repair-table tr:hover td { background: #f8fafc; }
.repair-table a { color: var(--r-blue); font-weight: 500; text-decoration: none; }
.repair-table a:hover { text-decoration: underline; }

.repair-bar { height: 6px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin: 10px 0 4px; }
.repair-bar span { display: block; height: 100%; border-radius: 6px; background: var(--r-blue); transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); }

.repair-trend { height: 180px; display: flex; align-items: flex-end; gap: 8px; margin: 20px 0 0; }
.repair-trend figure { flex: 1; min-width: 0; text-align: center; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; transition: var(--r-transition); cursor: pointer; group-hover: opacity: 0.8; }
.repair-trend figure:hover { transform: translateY(-2px); opacity: 1 !important; }
.repair-trend .bar { min-height: 4px; border-radius: 4px 4px 0 0; background: var(--r-blue); transition: height 0.5s ease-out; opacity: 0.85; }
.repair-trend figure:hover .bar { opacity: 1; background: var(--r-indigo); }
.repair-trend figcaption { font-size: 11px; color: #64748b; margin-top: 8px; white-space: nowrap; font-weight: 500; }
.repair-trend small { font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 6px; opacity: 0; transform: translateY(5px); transition: var(--r-transition); }
.repair-trend figure:hover small { opacity: 1; transform: translateY(0); }

.repair-filter { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; margin-bottom: 24px; background: transparent; }
.repair-filter .repair-field { flex: 1 1 200px; }

.repair-actions{display:flex;flex-wrap:wrap;gap:12px;align-items:center}
.repair-stack{display:flex;flex-direction:column;gap:28px}
.repair-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;max-height:290px;overflow:auto;margin-top:16px}
.repair-checks label{display:flex;align-items:center;gap:10px;font-size:14px;font-weight: 500; padding:14px;border-radius:10px;background: #f8fafc; border: 1px solid #cbd5e1; cursor: pointer; transition: var(--r-transition);}
.repair-checks label:hover { background: #ffffff; border-color: var(--r-blue); }
.repair-badge{display:inline-block;border-radius:6px;padding:4px 8px;font-size:12px;font-weight:600;background:#eff6ff;color:#1d4ed8;white-space:nowrap;}
.repair-notice{padding:16px;border-radius:8px;margin-bottom:24px;font-size:14px; font-weight: 500; display: flex; align-items: center; gap: 12px; border: 1px solid transparent;}
.repair-notice.success{background:#ecfdf5;color:#065f46; border-color: #a7f3d0;}
.repair-notice.error{background:#fef2f2;color:#991b1b; border-color: #fecaca;}
.repair-empty{text-align:center;padding:48px 24px;color:#64748b;font-size:14px; border-radius: var(--r-radius); border: 2px dashed #e2e8f0; background: #f8fafc;}
.repair-tabs{display:flex;overflow:auto;gap:4px;margin:24px 0; padding: 4px; background: #f1f5f9; border-radius: 8px; display: inline-flex;}
.repair-tabs a{padding:8px 16px;border-radius:6px;font-size:13px;font-weight:500;white-space:nowrap;color: #475569; text-decoration: none; transition: var(--r-transition);}
.repair-tabs a.active, .repair-tabs a:hover{background:white; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.05);}
.repair-summary{display:grid;grid-template-columns:2fr 1fr;gap:24px}
.repair-summary dl{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.repair-summary dt{font-size:12px;color:#64748b; font-weight: 500;}
.repair-summary dd{font-size:16px;font-weight:600;margin-top:4px;overflow-wrap:anywhere; color: #0f172a;}
.repair-total{display:flex;justify-content:space-between;padding:12px 0;font-size:14px;border-bottom:1px solid #e2e8f0}
.repair-total.grand{font-size:18px;font-weight:700;border-top:2px solid #e2e8f0;margin-top:8px; padding-top: 16px; color: #0f172a;}
.repair-timeline{border-left:2px solid #e2e8f0;margin-left:8px;padding-left:24px}
.repair-timeline article{position:relative;padding:0 0 24px;font-size:14px}
.repair-timeline article:before{position:absolute;content:'';width:10px;height:10px;background:var(--r-blue);border-radius:50%;left:-29px;top:4px; border: 2px solid white; box-sizing: content-box;}
.repair-timeline small{display:block;color:#64748b;margin-top:6px; font-size: 12px;}
.repair-photos{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.repair-photos img{width:100%;height:160px;object-fit:cover;border-radius:8px;background:#f1f5f9; border: 1px solid #e2e8f0;}
.repair-photos figcaption{font-size:12px;padding:8px 0; color: #475569; text-align: center;}
.repair-shell details summary{cursor:pointer;font-size:14px;font-weight:600;padding:12px 0; color: #0f172a;}
.repair-shell section[id]{scroll-margin-top:32px}
.repair-pattern{width:250px;height:250px;position:relative;touch-action:none;user-select:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:16px 0;}
.repair-pattern svg{position:absolute;inset:0;width:100%;height:100%;pointer-events:none}
.repair-pattern button{position:absolute;width:40px;height:40px;border-radius:50%;background:white;border:2px solid #cbd5e1;color:#64748b;font-size:14px;transform:translate(-50%,-50%);cursor:pointer;z-index:1; transition: var(--r-transition);}
.repair-pattern button:hover{border-color: var(--r-blue); color: var(--r-blue);}
.repair-pattern button.selected{background:var(--r-blue);border-color:var(--r-blue);color:white;}
.repair-search-results{border:1px solid #e2e8f0;border-radius:8px;max-height:300px;overflow:auto;background:white;margin-top:8px; box-shadow: var(--r-shadow-hover);}
.repair-search-results button{display:block;text-align:left;padding:12px 16px;width:100%;font-size:14px;border-bottom:1px solid #f1f5f9;cursor:pointer;}
.repair-search-results button:hover{background:#f8fafc;}
.repair-search-results p{padding:16px;font-size:14px;color:#64748b}
.repair-shell [hidden]{display:none!important}
.repair-credential{font-family:'Fira Code', monospace;font-size:18px;letter-spacing:2px;overflow-wrap:anywhere; background: #f8fafc; padding: 12px 16px; border-radius: 8px; display: inline-block; border: 1px solid #e2e8f0;}
.repair-flex{display:flex;gap:16px;flex-wrap:wrap;align-items:center}
.repair-catalog{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:16px}
.repair-table-wrap{overflow:auto;width:100%; border-radius: 8px; border: 1px solid #e2e8f0;}

/* Enhanced list items for metrics */
.repair-card > .repair-muted[href] { 
  display: flex; 
  flex-direction: column; 
  padding: 16px; 
  border: 1px solid transparent;
  border-bottom: 1px solid #f1f5f9; 
  transition: var(--r-transition); 
  text-decoration: none; 
  color: #1e293b;
  font-weight: 500;
  border-radius: 8px;
  margin-bottom: 4px;
}
.repair-card > .repair-muted[href]:last-child { border-bottom: 1px solid transparent; margin-bottom: 0; }
.repair-card > .repair-muted[href]:hover { background: #f8fafc; border: 1px solid #e2e8f0; padding-left: 20px; }
.repair-card > .repair-muted[href] b { font-weight: 600; font-size: 15px; color: #0f172a; }

@media(max-width:1024px){
  .repair-grid.four{grid-template-columns:repeat(2,minmax(0,1fr))}
  .repair-grid.three{grid-template-columns:repeat(2,minmax(0,1fr))}
  .repair-summary{grid-template-columns:1fr}
  .repair-photos{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media(max-width:640px){
  .repair-shell{padding:20px}
  .repair-heading{align-items:start;flex-direction:column;gap:12px;}
  .repair-heading h1{font-size:24px}
  .repair-grid,.repair-grid.three,.repair-fields,.repair-catalog{grid-template-columns:1fr}
  .repair-card{padding:20px}
  .repair-grid.four{gap:16px}
  .repair-metric{padding:20px}
  .repair-metric strong{font-size:28px}
  .repair-photos{grid-template-columns:repeat(2,minmax(0,1fr))}
  .repair-checks{grid-template-columns:1fr}
  .repair-table{font-size:12px}
  .repair-table th, .repair-table td { padding: 12px; }
  .repair-trend{gap:6px}
  .repair-trend figcaption{font-size:9px;writing-mode:vertical-rl;height:40px}
  .repair-summary dl{gap:16px}
  .repair-actions .repair-button{flex:1}
  .repair-filter .repair-field{flex-basis:100%}
}
</style>

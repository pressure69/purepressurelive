let _filterEnabled = false;

export function isFilterEnabled() {
  return _filterEnabled;
}

export function setFilterEnabled(value) {
  _filterEnabled = !!value;
  return _filterEnabled;
}

export function toggleFilter() {
  _filterEnabled = !_filterEnabled;
  return _filterEnabled;
}

export function defaultPredicate(p) {
  return typeof p?.value === "number" ? p.value >= 0 : true;
}

export function filterPoints(points, predicate = defaultPredicate) {
  if (!Array.isArray(points)) return [];
  return _filterEnabled ? points.filter(predicate) : points.slice();
}

export function drawPoints(points, drawPoint, predicate = defaultPredicate) {
  const toDraw = filterPoints(points, predicate);
  toDraw.forEach(drawPoint);
  return toDraw.length;
}

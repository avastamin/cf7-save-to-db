function truncateString(str: string, maxLength: number, omission = "...") {
  if (str.length > maxLength) {
    return str.slice(0, maxLength - omission.length) + omission;
  }
  return str;
}

export { truncateString };

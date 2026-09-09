import re
from collections import Counter
from datetime import datetime
from ocr_engine import clean_line_text


def _extract_annual_total(cleaned_lines):
    """Searches footer lines for valid annual summary figure."""
    keywords = [
        "за рік",
        "3a pik",
        "усього за",
        "разом за",
        "за рк",
        "за рія",
        "за pix",
    ]
    ignore_keywords = ["оформовано", "засобами", "стор.", "crop."]

    for line in reversed(cleaned_lines):
        line_lower = line.lower()
        if any(k in line_lower for k in ignore_keywords):
            continue
        if any(k in line_lower for k in keywords):
            matches = re.findall(r"\b\d+[.,]\d{2}\b|\b\d{5,7}\b", line)
            valid_vals = []
            for raw_s in matches:
                r_clean = raw_s.replace(",", ".")
                try:
                    v = float(r_clean)
                    if not (2000 <= v <= 2030):
                        valid_vals.append(v)
                except ValueError:
                    pass
            if valid_vals:
                return valid_vals[-1]
    return None


def _extract_candidate_salaries(block_text, annual_total):
    """Scans text for valid candidate monthly salary amounts (< 70% of annual_total)."""
    raw_matches = re.findall(r"\b\d+[.,]\d{2}\b", block_text)
    clean_salaries = []
    for m_str in raw_matches:
        try:
            val = float(m_str.replace(",", "."))
            if not (2000 <= val <= 2030) and 100.0 <= val < 300000.0:
                if annual_total is None or val < (annual_total * 0.7):
                    clean_salaries.append(round(val, 2))
        except ValueError:
            pass
    return clean_salaries


def _reconcile_monthly_amounts(annual_total, clean_salaries, is_latest):
    """
    Reconciles annual_total and candidate salaries into exact 12 monthly amounts.
    Evaluates:
    - Case A: Evenly divisible 12-month salary.
    - Case B: Dominant salary S + exact remainder matching candidates.
    - Case C: Evenly divisible N-month salary (1 <= N < 12).
    - Case D: Dominant frequency fallback.
    """
    # Case A: Check 12-month uniform division
    if annual_total and annual_total > 0:
        ideal_12 = round(annual_total / 12.0, 2)
        if round(ideal_12 * 12, 2) == annual_total:
            if (
                any(abs(c - ideal_12) < (ideal_12 * 0.1) for c in clean_salaries)
                or not clean_salaries
            ):
                return [f"{ideal_12:.2f}"] * 12

    # Case B: Check dominant salary S + exact remainder matching candidates
    if clean_salaries and annual_total and annual_total > 0:
        sal_counts = Counter(clean_salaries)
        for sal, _ in sal_counts.most_common():
            n_full = int(annual_total // sal)
            if 1 <= n_full <= 12:
                rem_val = round(annual_total - (n_full * sal), 2)
                if rem_val == 0 or any(abs(c - rem_val) < 1.0 for c in clean_salaries):
                    active = (
                        ([f"{rem_val:.2f}"] + [f"{sal:.2f}"] * n_full)
                        if rem_val > 10.0
                        else ([f"{sal:.2f}"] * n_full)
                    )
                    n_act = len(active)
                    return (
                        (active + ["0.00"] * (12 - n_act))
                        if is_latest
                        else (["0.00"] * (12 - n_act) + active)
                    )

    # Case C: Check N-month uniform division (1 <= N < 12)
    if annual_total and annual_total > 0:
        for n in range(11, 0, -1):
            s_cand = round(annual_total / float(n), 2)
            if round(s_cand * n, 2) == annual_total:
                if (
                    any(abs(c - s_cand) < (s_cand * 0.05) for c in clean_salaries)
                    or not clean_salaries
                ):
                    active = [f"{s_cand:.2f}"] * n
                    n_act = len(active)
                    return (
                        (active + ["0.00"] * (12 - n_act))
                        if is_latest
                        else (["0.00"] * (12 - n_act) + active)
                    )

    # Case D: Fallback quotient/remainder math using dominant salary
    full_sal = None
    if clean_salaries:
        sal_counts = Counter(clean_salaries)
        cands = [
            sal
            for sal, cnt in sal_counts.most_common()
            if not annual_total or 1 <= int(annual_total // sal) <= 12
        ]
        full_sal = cands[0] if cands else sal_counts.most_common(1)[0][0]

    if full_sal and annual_total and annual_total > 0:
        n_full = int(annual_total // full_sal)
        rem_val = round(annual_total - (n_full * full_sal), 2)
        active = (
            ([f"{rem_val:.2f}"] + [f"{full_sal:.2f}"] * n_full)
            if rem_val > 10.0
            else ([f"{full_sal:.2f}"] * n_full)
        )
        n_act = len(active)
        return (
            (active + ["0.00"] * (12 - n_act))
            if is_latest
            else (["0.00"] * (12 - n_act) + active)
        )

    if annual_total and annual_total > 0:
        u_val = round(annual_total / 12.0, 2)
        return [f"{u_val:.2f}"] * 12

    if len(clean_salaries) >= 12:
        return [f"{v:.2f}" for v in clean_salaries[:12]]

    return ["0.00"] * 12


def extract_from_year_block(year, is_latest, is_earliest, year_lines):
    """
    Extracts 12 monthly salary figures from a specific year block text lines.
    Delegates helper operations following Single Responsibility principle.
    """
    cleaned_lines = [clean_line_text(l) for l in year_lines]
    block_text = " ".join(cleaned_lines)

    annual_total = _extract_annual_total(cleaned_lines)
    clean_salaries = _extract_candidate_salaries(block_text, annual_total)
    monthly_amounts = _reconcile_monthly_amounts(
        annual_total, clean_salaries, is_latest
    )

    print(
        f"[OK-5 Parser] Extracted year {year} final values: {monthly_amounts[:12]}",
        flush=True,
    )

    records = []
    for month_idx, amt in enumerate(monthly_amounts[:12], start=1):
        records.append({"year": str(year), "month": month_idx, "salary_amount": amt})
    return records


def parse_ok5_document(full_text):
    """
    Parses Ukrainian OK-5 document block-by-block per year (Reported year: YYYY).
    Guarantees each reported year table is processed EXACTLY ONCE with 12 clean monthly records.
    """
    lines = [line.strip() for line in full_text.split("\n") if line.strip()]

    doc_year = datetime.now().year
    for l in reversed(lines):
        m = re.search(r"\b\d{2}[./]\d{2}[./](\d{4})\b", l)
        if m and 2000 <= int(m.group(1)) <= 2030:
            doc_year = int(m.group(1))
            break

    raw_blocks = []
    curr_block = []

    for l in lines:
        is_yr_line = bool(re.search(r"звітний\s*(?:рік|pic)", l, re.IGNORECASE))
        is_ved_line = any(
            k in l.lower() for k in ["відомості за звітний", "відомості a звітний"]
        )
        is_tot_line = any(
            k in l.lower() for k in ["усього за рік", "усього за рік для пенсії"]
        )

        if (is_yr_line or is_ved_line) and curr_block:
            curr_text = " ".join(curr_block)
            if any(
                k in curr_text.lower()
                for k in ["усього", "гри", "грн", "чисельник", "знаменник", "0.00"]
            ):
                raw_blocks.append(curr_block)
                curr_block = [l]
            else:
                curr_block.append(l)
        elif is_tot_line:
            curr_block.append(l)
            raw_blocks.append(curr_block)
            curr_block = []
        else:
            curr_block.append(l)

    if curr_block:
        raw_blocks.append(curr_block)

    valid_blocks = []
    for b in raw_blocks:
        b_text = " ".join(b).lower()
        if any(
            k in b_text
            for k in [
                "усього за рік",
                "усього, грн",
                "чисельник",
                "знаменник",
                "сума заробітку",
                "доплата",
            ]
        ):
            valid_blocks.append(b)

    if not valid_blocks:
        valid_blocks = [lines]

    num_blocks = len(valid_blocks)
    start_year = doc_year - num_blocks + 1

    records = []
    for idx, b in enumerate(valid_blocks):
        b_text = " ".join(b)
        m_yr = re.search(r"звітний\s*(?:рік|pic)[\s:]*(20\d{2})", b_text, re.IGNORECASE)
        year = int(m_yr.group(1)) if m_yr else (start_year + idx)

        is_latest = idx == len(valid_blocks) - 1
        is_earliest = idx == 0
        records.extend(extract_from_year_block(year, is_latest, is_earliest, b))

    return records
